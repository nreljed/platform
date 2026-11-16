import * as React from "react";
import { cn } from "../../lib/utils";
import { Card, CardContent } from "../ui/card";
import { Eye } from "lucide-react";

interface PreviewCardProps {
  label?: string;
  /** The previewed value (e.g. formatted price). */
  value: React.ReactNode;
  /** Optional secondary line. */
  hint?: string;
  className?: string;
}

/**
 * Compact "live preview" tile used next to formatting settings so the user
 * sees the effect of their changes immediately.
 */
export function PreviewCard({ label = "Preview", value, hint, className }: PreviewCardProps) {
  return (
    <div
      className={cn(
        "rounded-lg border border-dashed border-border bg-muted/40 px-4 py-3",
        className,
      )}
    >
      <div className="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-muted-foreground">
        <Eye className="h-3.5 w-3.5" />
        {label}
      </div>
      <div className="mt-1 text-lg font-semibold text-foreground tabular-nums">{value}</div>
      {hint && <p className="mt-0.5 text-xs text-muted-foreground">{hint}</p>}
    </div>
  );
}

export default PreviewCard;
