import { router, usePage } from "@inertiajs/react";
import { AlertTriangle, LogOut } from "lucide-react";

interface Impersonator {
  id: number;
  name: string;
  email: string;
}

export default function ImpersonationBanner() {
  const pageProps = usePage().props as Record<string, unknown>;
  const impersonating = pageProps.impersonating as boolean;
  const impersonator = pageProps.impersonator as Impersonator | null;

  if (!impersonating || !impersonator) return null;

  const handleStop = () => {
    router.post(route("dashboard.admin.impersonate.stop"));
  };

  return (
    <div className="bg-warning/10 border-b border-warning/20 dark:bg-warning/15 dark:border-warning/30 px-4 py-2">
      <div className="mx-auto flex max-w-7xl items-center justify-between">
        <div className="flex items-center gap-2 text-sm text-warning dark:text-warning">
          <AlertTriangle className="size-4" />
          <span>
            You are acting as <strong>{impersonator.name}</strong> ({impersonator.email})
          </span>
        </div>
        <button
          onClick={handleStop}
          className="inline-flex items-center gap-1.5 rounded-md bg-warning dark:bg-warning px-3 py-1 text-xs font-medium text-white shadow-sm transition-colors hover:bg-warning dark:hover:bg-warning"
        >
          <LogOut className="size-3.5" />
          Stop Impersonating
        </button>
      </div>
    </div>
  );
}
