import * as React from "react";
import { cn } from "../../lib/utils";
import { Button } from "../ui/button";
import { Save, RotateCcw, AlertTriangle, Check } from "lucide-react";

interface StickySaveBarProps {
  /** Whether the form has unsaved changes. */
  dirty: boolean;
  /** Whether a save is in flight. */
  processing?: boolean;
  /** Number of validation errors. */
  errorCount?: number;
  /** Called when the user clicks Save. */
  onSave: () => void;
  /** Called when the user clicks Discard. */
  onDiscard?: () => void;
  /** Optional label override. */
  saveLabel?: string;
  /** Optional description. */
  description?: string;
  className?: string;
}

/**
 * Slide-up bar that only appears when the form is dirty. Gives a clear,
 * always-visible answer to "did my changes save?" and prevents lost edits.
 */
export function StickySaveBar({
  dirty,
  processing = false,
  errorCount = 0,
  onSave,
  onDiscard,
  saveLabel = "Save changes",
  description,
  className,
}: StickySaveBarProps) {
  return (
    <div
      aria-live="polite"
      className={cn(
        "fixed bottom-4 left-1/2 z-40 -translate-x-1/2 transition-all duration-200",
        dirty
          ? "translate-y-0 opacity-100"
          : "pointer-events-none translate-y-6 opacity-0",
        className,
      )}
    >
      <div
        className={cn(
          "flex items-center gap-3 rounded-full border bg-popover/95 px-4 py-2 shadow-lg backdrop-blur",
          errorCount > 0 && "border-destructive/40",
        )}
      >
        <span className="flex items-center gap-2 text-sm font-medium text-foreground">
          <span
            className={cn(
              "h-2 w-2 rounded-full",
              errorCount > 0 ? "bg-destructive" : "bg-amber-500",
            )}
          />
          {description ?? (errorCount > 0 ? `${errorCount} error${errorCount === 1 ? "" : "s"} to fix` : "Unsaved changes")}
        </span>
        {onDiscard && (
          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={onDiscard}
            disabled={processing}
            className="rounded-full"
          >
            <RotateCcw className="h-3.5 w-3.5" />
            Discard
          </Button>
        )}
        <Button
          type="button"
          size="sm"
          onClick={onSave}
          disabled={processing}
          className="rounded-full"
        >
          <Save className="h-3.5 w-3.5" />
          {processing ? "Saving…" : saveLabel}
        </Button>
      </div>
    </div>
  );
}

export default StickySaveBar;
