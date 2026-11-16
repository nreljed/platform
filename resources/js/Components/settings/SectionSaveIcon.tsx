import * as React from "react";
import { cn } from "../../lib/utils";
import { Badge } from "../ui/badge";
import { Check, AlertCircle, CircleDot } from "lucide-react";

interface SectionSaveIconProps {
  /** Whether this section has unsaved changes. */
  dirty?: boolean;
  /** Number of validation errors in this section. */
  errorCount?: number;
  /** Whether this section was just saved. */
  saved?: boolean;
  /** Optional count of changed fields. */
  changedCount?: number;
  className?: string;
}

/**
 * Small trailing indicator for accordion sections: amber dot when dirty,
 * red badge when errors, green check when just saved.
 */
export function SectionSaveIcon({
  dirty,
  errorCount = 0,
  saved = false,
  changedCount,
  className,
}: SectionSaveIconProps) {
  if (errorCount > 0) {
    return (
      <Badge variant="destructive" className={cn("h-5 px-1.5 text-xs", className)}>
        <AlertCircle className="h-3 w-3 mr-1" />
        {errorCount}
      </Badge>
    );
  }
  if (dirty) {
    return (
      <span
        className={cn(
          "inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400",
          className,
        )}
      >
        <CircleDot className="h-3 w-3" />
        {changedCount ? `${changedCount} changed` : "Modified"}
      </span>
    );
  }
  if (saved) {
    return (
      <span
        className={cn(
          "inline-flex items-center gap-1 rounded-full bg-status-success/15 px-2 py-0.5 text-xs font-medium text-status-success",
          className,
        )}
      >
        <Check className="h-3 w-3" />
        Saved
      </span>
    );
  }
  return null;
}

export default SectionSaveIcon;
