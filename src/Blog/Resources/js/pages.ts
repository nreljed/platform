import type { ComponentType } from "react";

export type PageImport = () => Promise<{ default: ComponentType<unknown> }>;

export const blogPagePrefix = "blog";

/**
 * The BLOG feature page kit. Returns the blog module's frontend page map
 * (admin + editor pages) under the `blog/` prefix — e.g. `blog/index`,
 * `blog/form`, `blog/dashboard`, `blog/categories/index`, `blog/tags/index`.
 *
 * Controllers render lowercase names (Blog/Index → `blog/index`), so these
 * keys must stay lowercase. Consuming apps spread the returned map into their
 * pageMap alongside registerCoreTemplates(); each feature module ships its own
 * register{Name}Pages() export that the app imports once in page-config.
 */
export function registerBlogPages(): Record<string, PageImport> {
    const pageMap: Record<string, PageImport> = {};
    const pages = import.meta.glob("./Pages/**/*.tsx") as Record<string, PageImport>;

    for (const [path, importFn] of Object.entries(pages)) {
        const componentPath = path.match(/\/Pages\/(.+)\.tsx$/)?.[1];

        if (componentPath) {
            pageMap[`${blogPagePrefix}/${componentPath.toLowerCase()}`] = importFn;
        }
    }

    return pageMap;
}