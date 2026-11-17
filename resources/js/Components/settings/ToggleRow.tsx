import * as React from "react";
import { cn } from "../../lib/utils";
import { Label } from "../ui/label";
import { Switch } from "../ui/switch";

interface ToggleRowProps {
  label: string;
  description?: string;
  checked: boolean;
  onCheckedChange: (value: boolean) => void;
  disabled?: boolean;
  /** When provided, the row is muted and disabled unless this is true. */
  dependsOn?: boolean;
  dependsOnLabel?: string;
  className?: string;
}

export function ToggleRow({
  label,
  description,
  checked,
  onCheckedChange,
  disabled = false,
  dependsOn,
  dependsOnLabel,
  className,
}: ToggleRowProps) {
  const inactive = dependsOn === false;
  return (
    <div
      className={cn(
        "flex items-center justify-between gap-4 rounded-lg border p-3 transition-colors",
        inactive && "opacity-50",
        className,
      )}
    >
      <div className="space-y-0.5 min-w-0">
        <Label className="font-medium text-foreground">{label}</Label>
        {description && (
          <p className="text-xs text-muted-foreground">{description}</p>
        )}
        {inactive && dependsOnLabel && (
          <p className="text-xs text-warning dark:text-warning">
            Enable {dependsOnLabel} first.
          </p>
        )}
      </div>
      <Switch
        checked={checked}
        onCheckedChange={(v) => onCheckedChange(v === true)}
        disabled={disabled || inactive}
      />
    </div>
  );
}

interface ToggleGroupProps {
  title?: string;
  description?: string;
  children: React.ReactNode;
  className?: string;
}

export function ToggleGroup({ title, description, children, className }: ToggleGroupProps) {
  return (
    <div className={cn("space-y-2", className)}>
      {(title || description) && (
        <div className="space-y-0.5">
          {title && <p className="text-sm font-medium text-foreground">{title}</p>}
          {description && <p className="text-xs text-muted-foreground">{description}</p>}
        </div>
      )}
      <div className="space-y-2">{children}</div>
    </div>
  );
}

export default ToggleRow;
