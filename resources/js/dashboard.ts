import type { ComponentType } from "react";

export type PageImport = () => Promise<{ default: ComponentType<unknown> }>;

/**
 * The opt-in PLATFORM page kit. Returns the standard control-panel page map
 * (dashboard, admin, account, auth, error). Template / marketing pages are
 * NOT here - themes are project-owned (see docs/frontend.md). Feature pages
 * are NOT here
 * either — each feature module ships its own `register{Name}Pages()` export
 * (e.g. `registerPostsPages()` from the Posts module) that consuming apps
 * import explicitly into their pageConfig.
 *
 * Consuming apps decide which pages they use by spreading this into their
 * own pageMap - core never auto-registers pages.
 */
export function registerCoreTemplates(): Record<string, PageImport> {
    const pageMap: Record<string, PageImport> = {};

    const corePages = import.meta.glob("./Pages/**/*.tsx") as Record<string, PageImport>;

    for (const [path, importFn] of Object.entries(corePages)) {
        const componentPath = path.match(/\/Pages\/(.+)\.tsx$/)?.[1];

        if (componentPath) {
            pageMap[componentPath.toLowerCase()] = importFn;
        }
    }

    return pageMap;
}