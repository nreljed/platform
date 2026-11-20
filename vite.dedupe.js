/**
 * Package deduplication list for Vite.
 *
 * Consuming apps should spread this into their vite.config.js resolve.dedupe
 * to prevent duplicate bundles when multiple packages (platform,
 * shop-module, etc.) share the same React/Inertia/Lexical dependencies.
 *
 * Usage in consuming app:
 *   import { packageDedupes } from "@nreljed/platform/vite-dedupes";
 *   // or for local dev:
 *   import { packageDedupes } from "../packages/platform/vite.dedupe.js";
 *
 *   export default defineConfig({
 *     resolve: { dedupe: packageDedupes },
 *   });
 */
export const packageDedupes = [
    '@base-ui/react',
    'cn',
    '@dnd-kit/core',
    '@dnd-kit/sortable',
    '@dnd-kit/utilities',
    '@geoman-io/leaflet-geoman-free',
    '@inertiajs/core',
    '@inertiajs/react',
    '@shadcn/react',
    '@tanstack/react-table',
    '@zxcvbn-ts/core',
    '@zxcvbn-ts/language-common',
    '@zxcvbn-ts/language-en',
    'class-variance-authority',
    'cmdk',
    'date-fns',
    'embla-carousel-react',
    'input-otp',
    'leaflet',
    'libphonenumber-js',
    'lucide-react',
    'next-themes',
    'react',
    'react-day-picker',
    'react-dom',
    'react-dropzone',
    'react-hook-form',
    'react-leaflet',
    'react-leaflet-cluster',
    'react-phone-number-input',
    'react-resizable-panels',
    'recharts',
    'tw-animate-css',
    'ziggy-js',
    'zod',
];
