import * as React from "react";
import { router } from "@inertiajs/react";
import { toast } from "../Components/ui/toast";

/**
 * Deep comparison of two plain JSON-serialisable values.
 * Settings forms only ever hold primitives/arrays/objects, so JSON
 * serialisation is sufficient and avoids a dependency on lodash.
 */
function deepEqual(a: unknown, b: unknown): boolean {
  try {
    return JSON.stringify(a) === JSON.stringify(b);
  } catch {
    return a === b;
  }
}

export type SettingsMethod = "get" | "post" | "put" | "patch" | "delete";

export interface UseSettingsFormOptions<T extends Record<string, unknown>> {
  /** Initial (server-provided) values. */
  initial: T;
  /** Inertia route/url to submit to. */
  submitUrl: string;
  /** HTTP method, defaults to `put`. */
  method?: SettingsMethod;
  /** Message shown in the toast on success. */
  successMessage?: string;
  /** Message shown in the toast on failure. */
  errorMessage?: string;
  /** Extra onSuccess callback. */
  onSuccess?: () => void;
  /** Extra onError callback. */
  onError?: (errors: Record<string, string>) => void;
}

export interface UseSettingsFormResult<T extends Record<string, unknown>> {
  values: T;
  setField: <K extends keyof T>(key: K, value: T[K]) => void;
  setValues: React.Dispatch<React.SetStateAction<T>>;
  submit: (e?: React.FormEvent) => void;
  discard: () => void;
  isDirty: boolean;
  errors: Record<string, string>;
  processing: boolean;
  saved: T;
}

/**
 * Settings form hook built on top of Inertia's router.
 *
 * Adds the things plain `useForm`/`router.put` don't give us out of the box:
 *  - dirty tracking against the last-saved snapshot
 *  - `discard()` to revert
 *  - unsaved-changes guard (beforeunload + Inertia `before` event)
 *  - automatic re-sync when the server-provided `initial` changes
 */
export function useSettingsForm<T extends Record<string, unknown>>(
  options: UseSettingsFormOptions<T>,
): UseSettingsFormResult<T> {
  const {
    initial,
    submitUrl,
    method = "put",
    successMessage = "Settings saved.",
    errorMessage = "Failed to save. Check the form for errors.",
    onSuccess,
    onError,
  } = options;

  const [values, setValues] = React.useState<T>(initial);
  const [saved, setSaved] = React.useState<T>(initial);
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const [processing, setProcessing] = React.useState(false);

  // Re-sync when the server payload changes (e.g. after a reload).
  const initialKey = React.useMemo(() => JSON.stringify(initial), [initial]);
  React.useEffect(() => {
    setValues(initial);
    setSaved(initial);
    setErrors({});
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initialKey]);

  const isDirty = React.useMemo(() => !deepEqual(values, saved), [values, saved]);

  const setField = React.useCallback(
    <K extends keyof T>(key: K, value: T[K]) => {
      setValues((prev) => ({ ...prev, [key]: value }));
    },
    [],
  );

  const discard = React.useCallback(() => {
    setValues(saved);
    setErrors({});
  }, [saved]);

  // Unsaved-changes guard.
  React.useEffect(() => {
    if (!isDirty) return;
    const onBeforeUnload = (e: BeforeUnloadEvent) => {
      e.preventDefault();
      e.returnValue = "";
    };
    window.addEventListener("beforeunload", onBeforeUnload);
    const remove = router.on("before", (event) => {
      if (!isDirty) return;
      const ok = window.confirm("You have unsaved changes. Discard them and leave?");
      if (!ok) event.preventDefault();
    });
    return () => {
      window.removeEventListener("beforeunload", onBeforeUnload);
      remove();
    };
  }, [isDirty]);

  const submit = React.useCallback(
    (e?: React.FormEvent) => {
      e?.preventDefault();
      if (processing) return;
      setProcessing(true);
      setErrors({});
      (router as unknown as Record<string, (url: string, values: unknown, opts: unknown) => void>)[method]!(submitUrl, values, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: (page: unknown) => {
          const props = (page as { props?: Record<string, unknown> }).props;
          // Prefer server-provided fresh settings when available.
          const fresh =
            (props?.settings as T | undefined) ??
            (props?.shopSettings as T | undefined) ??
            (props?.data as T | undefined);
          const next = fresh && typeof fresh === "object" ? fresh : values;
          setSaved(next);
          setValues(next);
          toast.add({ title: successMessage, type: "success" });
          onSuccess?.();
        },
        onError: (errs: unknown) => {
          const mapped = (errs ?? {}) as Record<string, string>;
          setErrors(mapped);
          toast.add({ title: errorMessage, type: "error" });
          onError?.(mapped);
        },
        onFinish: () => setProcessing(false),
      });
    },
    [values, processing, method, submitUrl, successMessage, errorMessage, onSuccess, onError],
  );

  return { values, setField, setValues, submit, discard, isDirty, errors, processing, saved };
}

/**
 * Lightweight dirty-state helper for pages that already use Inertia's `useForm`.
 * Returns whether `current` differs from `initial`.
 */
export function useDirtyState<T>(initial: T, current: T): boolean {
  return React.useMemo(() => !deepEqual(initial, current), [initial, current]);
}
export { deepEqual };
