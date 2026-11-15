import assert from 'node:assert/strict';
import { randomBytes } from 'node:crypto';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { createServer } from 'node:net';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { spawn, spawnSync } from 'node:child_process';
import { chromium } from 'playwright';
import jsQR from 'jsqr';
import { PNG } from 'pngjs';

const temporary = await mkdtemp(join(tmpdir(), 'medicina-browser-'));
const socket = createServer();
await new Promise((resolve) => socket.listen(0, '127.0.0.1', resolve));
const port = socket.address().port;
await new Promise((resolve) => socket.close(resolve));
const base = `http://127.0.0.1:${port}`;
const database = join(temporary, 'test.sqlite');
await writeFile(database, '');
const environment = {
    ...process.env, APP_ENV: 'local', APP_DEBUG: 'false', APP_URL: base,
    APP_KEY: `base64:${randomBytes(32).toString('base64')}`,
    DB_CONNECTION: 'sqlite', DB_DATABASE: database, PATIENT_PHOTO_ROOT: join(temporary, 'photos'),
    SESSION_DRIVER: 'database', SESSION_SECURE_COOKIE: 'false',
    CACHE_STORE: 'array', LOG_CHANNEL: 'stderr', MAIL_MAILER: 'array',
};
function artisan(...args) {
    const result = spawnSync('php', ['artisan', ...args], { env: environment, encoding: 'utf8' });
    assert.equal(result.status, 0, result.stderr || result.stdout);
}
let server;
let browser;
let checks = 0;
let serverOutput = '';
const completed = (name) => { checks++; console.log(`PASS ${name}`); };
try {
    artisan('migrate', '--force');
    server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${port}`, '--no-reload'], { env: environment, detached: true, stdio: ['ignore', 'pipe', 'pipe'] });
    for (const pipe of [server.stdout, server.stderr]) pipe.on('data', (chunk) => { serverOutput = (serverOutput + chunk).slice(-8000); });
    let ready = false;
    for (let attempt = 0; attempt < 100; attempt++) {
        try { if ((await fetch(`${base}/up`)).ok) { ready = true; break; } } catch {}
        await new Promise((resolve) => setTimeout(resolve, 100));
    }
    assert.ok(ready, serverOutput);
    browser = await chromium.launch({
        executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE || undefined,
        args: ['--use-fake-device-for-media-stream', '--use-fake-ui-for-media-stream'],
    });
    const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await context.newPage();
    const pageErrors = [];
    page.on('pageerror', (error) => pageErrors.push(error.message));
    await page.goto(base);
    await page.getByRole('heading', { name: 'Pacientes y médicos, en un solo lugar.' }).waitFor();
    if (process.env.SMOKE_SCREENSHOT) await page.screenshot({ path: process.env.SMOKE_SCREENSHOT, fullPage: true });
    const guest = await browser.newContext();
    const denied = await guest.request.get(`${base}/lista`, { maxRedirects: 0 });
    assert.equal(denied.status(), 302);
    assert.ok(denied.headers().location.endsWith('/login'));
    completed('public landing and guest authentication barrier');

    const password = randomBytes(24).toString('base64url');
    await page.goto(`${base}/register`);
    await page.locator('[name=name]').fill('Cuenta de prueba sintética');
    await page.locator('[name=email]').fill('browser@example.test');
    await page.locator('[name=password]').fill(password);
    await page.locator('[name=password_confirmation]').fill(password);
    await page.getByRole('button', { name: 'Registrarse' }).click();
    await page.waitForURL(`${base}/home`);
    assert.ok((await page.textContent('body')).includes('pendiente de autorización'));
    assert.equal((await context.request.get(`${base}/lista`)).status(), 403);
    artisan('app:authorize-staff', 'browser@example.test');
    await page.reload();
    await page.getByText('Gestionar pacientes').waitFor();
    completed('registration, pending approval and explicit staff authorization');
    assert.equal((await context.request.post(`${base}/formMedico`, { form: { nombre: 'Sin token', especialidad: 'General' } })).status(), 419);
    completed('real HTTP requests reject missing CSRF tokens');

    const doctorIds = [];
    for (const name of ['Médico ficticio A', 'Médico ficticio B']) {
        await page.goto(`${base}/formMedico`);
        await page.locator('#nombre').fill(name);
        await page.locator('#especialidad').fill('Especialidad de prueba');
        await page.getByRole('button', { name: 'Registrar médico', exact: true }).click();
        await page.waitForURL(`${base}/listaMedico`);
        const row = page.locator('tbody tr').filter({ hasText: name });
        const edit = await row.getByRole('link', { name: 'Editar' }).getAttribute('href');
        doctorIds.push(edit.match(/formMedico\/(\d+)\/edit/)[1]);
    }
    await page.goto(`${base}/formMedico/${doctorIds[0]}/edit`);
    await page.locator('#especialidad').fill('Especialidad actualizada');
    await page.getByRole('button', { name: 'Guardar cambios' }).click();
    await page.waitForURL(`${base}/listaMedico`);
    completed('doctor creation, listing and editing');

    const image = new PNG({ width: 120, height: 80 });
    for (let i = 0; i < image.data.length; i += 4) { image.data[i] = 30; image.data[i + 1] = 120; image.data[i + 2] = 180; image.data[i + 3] = 255; }
    const imageBuffer = PNG.sync.write(image);
    const patients = [];
    for (const mode of ['upload', 'camera']) {
        await page.goto(`${base}/form`);
        await page.locator('#nombre').fill(`Paciente sintético ${mode}`);
        await page.locator('#apellido').fill('Prueba');
        await page.locator('#fecha_nacimiento').fill('1990-01-01');
        await page.locator('#enfermedades').fill('Registro exclusivamente ficticio');
        await page.locator('#medico_id').selectOption(doctorIds);
        await page.getByRole('button', { name: 'Añadir alergia' }).click();
        await page.locator('[name="alergia[]"]').last().fill('Polen');
        await page.locator('.remove-allergy').first().click();
        if (mode === 'upload') {
            await page.locator('#url').setInputFiles({ name: 'synthetic.png', mimeType: 'image/png', buffer: imageBuffer });
        } else {
            await page.getByRole('button', { name: 'Usar cámara' }).click();
            await page.waitForFunction(() => document.getElementById('video').srcObject?.active);
            const cameraTrack = await page.locator('#video').evaluateHandle((video) => video.srcObject.getVideoTracks()[0]);
            await page.getByRole('button', { name: 'Cerrar cámara' }).click();
            assert.equal(await cameraTrack.evaluate((track) => track.readyState), 'ended');
            assert.equal(await page.locator('#video').evaluate((video) => video.srcObject), null);
            await cameraTrack.dispose();
            completed('closing the camera releases its video track');
            await page.getByRole('button', { name: 'Usar cámara' }).click();
            await page.getByRole('button', { name: 'Capturar foto' }).click();
            await page.getByText('Fotografía capturada.', { exact: false }).waitFor();
            assert.equal(await page.locator('#video').evaluate((video) => video.srcObject), null);
            assert.equal(await page.locator('#url').evaluate((input) => input.files.length), 1);
        }
        await page.getByRole('button', { name: 'Registrar paciente', exact: true }).click();
        await page.waitForURL(`${base}/lista`);
        const row = page.locator('tbody tr').filter({ hasText: `Paciente sintético ${mode}` });
        await row.waitFor();
        assert.ok((await row.textContent()).includes('Médico ficticio A'));
        assert.ok((await row.textContent()).includes('Médico ficticio B'));
        const href = await row.getByRole('link', { name: 'Editar' }).getAttribute('href');
        const id = href.match(/form\/(\d+)\/edit/)[1];
        patients.push(id);
        const photo = await context.request.get(`${base}/paciente/${id}/foto`);
        assert.equal(photo.status(), 200);
        assert.equal(photo.headers()['content-type'], 'image/jpeg');
        completed(`${mode} photo, multiple doctors and patient creation`);
    }
    const id = patients[0];
    await page.goto(`${base}/form/${id}/edit`);
    assert.deepEqual(await page.locator('#medico_id').evaluate((select) => Array.from(select.selectedOptions, (o) => o.value)), doctorIds);
    assert.equal(await page.locator('[name="alergia[]"]').inputValue(), 'Polen');
    await page.locator('#apellido').fill('Actualizado');
    await page.getByRole('button', { name: 'Guardar cambios' }).click();
    await page.waitForURL(`${base}/lista`);
    assert.equal((await context.request.get(`${base}/paciente/${id}/foto`)).status(), 200);
    const pdf = await context.request.get(`${base}/paciente/pdf/${id}`);
    assert.equal(pdf.status(), 200);
    const pdfBuffer = await pdf.body();
    assert.ok(pdfBuffer.subarray(0, 5).equals(Buffer.from('%PDF-')));
    assert.ok(pdfBuffer.includes(Buffer.from('/Subtype /Image')));
    completed('patient edit retains assignments and photo; PDF embeds image');

    const qrPage = await context.newPage();
    await qrPage.goto(`${base}/lista/qr/${id}`);
    const qrPng = PNG.sync.read(await qrPage.locator('svg').screenshot());
    const decoded = jsQR(new Uint8ClampedArray(qrPng.data), qrPng.width, qrPng.height);
    assert.equal(decoded?.data, `${base}/paciente/pdf/${id}`);
    assert.equal((await guest.request.get(decoded.data, { maxRedirects: 0 })).status(), 302);
    await qrPage.close();
    completed('QR decodes to the correct PDF URL and does not bypass authentication');

    await page.goto(`${base}/form`);
    await page.setViewportSize({ width: 375, height: 812 });
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true);
    await page.locator('#url').scrollIntoViewIfNeeded();
    await page.addInitScript(() => { navigator.mediaDevices.getUserMedia = async () => { throw new DOMException('Denied', 'NotAllowedError'); }; });
    await page.reload();
    await page.getByRole('button', { name: 'Usar cámara' }).click();
    await page.getByText('No se pudo abrir la cámara.', { exact: false }).waitFor();
    completed('mobile form fits viewport and camera permission errors are visible');

    const token = await page.locator('[name=_token]').first().inputValue();
    const protectedDoctor = await context.request.delete(`${base}/formMedico/${doctorIds[0]}`, { form: { _token: token }, maxRedirects: 0 });
    assert.equal(protectedDoctor.status(), 302);
    assert.ok((await (await context.request.get(`${base}/listaMedico`)).text()).includes('No se puede eliminar un médico con pacientes asignados'));
    for (const patient of patients) {
        assert.equal((await context.request.delete(`${base}/form/${patient}`, { form: { _token: token }, maxRedirects: 0 })).status(), 302);
        assert.equal((await context.request.get(`${base}/paciente/${patient}/foto`)).status(), 404);
    }
    for (const doctor of doctorIds) assert.equal((await context.request.delete(`${base}/formMedico/${doctor}`, { form: { _token: token }, maxRedirects: 0 })).status(), 302);
    completed('assigned doctors are protected; patient and unassigned doctor deletion works');

    const logout = await context.request.post(`${base}/logout`, { form: { _token: token }, maxRedirects: 0 });
    assert.equal(logout.status(), 302);
    await page.goto(`${base}/login`);
    await page.locator('[name=email]').fill('browser@example.test');
    await page.locator('[name=password]').fill(password);
    await page.getByRole('button', { name: /Acceder/ }).click();
    await page.waitForURL(`${base}/home`);
    completed('logout and subsequent login');
    assert.deepEqual(pageErrors, []);
    console.log(`Browser smoke: ${checks} checks passed (Chromium; synthetic camera and data).`);
} catch (error) {
    console.error(serverOutput);
    throw error;
} finally {
    await browser?.close();
    if (server) {
        try { process.kill(-server.pid, 'SIGTERM'); } catch {}
    }
    await rm(temporary, { recursive: true, force: true });
}
