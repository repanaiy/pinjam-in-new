/**
 * Build script untuk production assets.
 *
 * Sumber nilai VITE_REVERB_* (berurutan prioritas):
 *  1. Environment variable yang sudah di-export di shell (CI / Railway / PowerShell session)
 *  2. File .env.production di root project
 *  3. Fallback ke nilai default lokal
 *
 * Cara pakai:
 *   npm run build:prod            # pakai .env.production jika ada
 *   $env:VITE_REVERB_HOST="..."   # override via shell (PowerShell)
 *   npm run build:prod -- --force # paksa rebuild walau cache ada
 */

import { existsSync, readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const __dirname = dirname(fileURLToPath(import.meta.url));
const rootDir = resolve(__dirname, '..');

const DEFAULTS = {
    VITE_REVERB_APP_KEY: 'pinjaminkey',
    VITE_REVERB_HOST: 'localhost',
    VITE_REVERB_PORT: '8080',
    VITE_REVERB_SCHEME: 'http',
};

/**
 * Parse file .env sederhana (KEY=VALUE per baris,忽略 komentar dan tanda kutip).
 */
function parseEnvFile(filePath) {
    if (!existsSync(filePath)) {
        return {};
    }

    const content = readFileSync(filePath, 'utf8');
    const result = {};

    for (const rawLine of content.split(/\r?\n/)) {
        const line = rawLine.trim();

        if (!line || line.startsWith('#')) {
            continue;
        }

        const separatorIndex = line.indexOf('=');

        if (separatorIndex === -1) {
            continue;
        }

        const key = line.slice(0, separatorIndex).trim();
        let value = line.slice(separatorIndex + 1).trim();

        // Buang tanda kutip pembungkus
        if (
            (value.startsWith('"') && value.endsWith('"')) ||
            (value.startsWith("'") && value.endsWith("'"))
        ) {
            value = value.slice(1, -1);
        }

        result[key] = value;
    }

    return result;
}

function buildEnv() {
    const envFileValues = parseEnvFile(resolve(rootDir, '.env.production'));
    const env = { ...DEFAULTS, ...envFileValues };

    // Environment variable shell selalu menang
    for (const key of Object.keys(DEFAULTS)) {
        if (process.env[key] !== undefined && process.env[key] !== '') {
            env[key] = process.env[key];
        }
    }

    return env;
}

function writeBuildMetadata(env) {
    const buildDir = resolve(rootDir, 'public', 'build');

    if (!existsSync(buildDir)) {
        mkdirSync(buildDir, { recursive: true });
    }

    const metadata = {
        builtAt: new Date().toISOString(),
        reverb: {
            appKey: env.VITE_REVERB_APP_KEY,
            host: env.VITE_REVERB_HOST,
            port: env.VITE_REVERB_PORT,
            scheme: env.VITE_REVERB_SCHEME,
        },
    };

    writeFileSync(
        resolve(buildDir, 'build-info.json'),
        `${JSON.stringify(metadata, null, 2)}\n`,
        'utf8'
    );
}

function main() {
    const env = buildEnv();

    console.log('--- Vite build environment ---');
    for (const [key, value] of Object.entries(env)) {
        // Jangan bocorkan secret penuh di log
        const display = key.includes('KEY') || key.includes('SECRET')
            ? `${value.slice(0, 4)}***`
            : value;
        console.log(`${key}=${display}`);
    }
    console.log('-----------------------------');

    // Set env untuk subprocess Vite
    const childEnv = { ...process.env, ...env };

    const result = spawnSync('npx', ['vite', 'build'], {
        cwd: rootDir,
        stdio: 'inherit',
        env: childEnv,
        shell: process.platform === 'win32',
    });

    if (result.status !== 0) {
        console.error('\n[build-assets] Vite build GAGAL.');
        process.exit(result.status ?? 1);
    }

    writeBuildMetadata(env);
    console.log('\n[build-assets] Build selesai. Metadata ditulis ke public/build/build-info.json');
}

main();
