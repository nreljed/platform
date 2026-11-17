import * as React from "react";
import { cn } from "../../lib/utils";
import {
  Field,
  FieldLabel,
  FieldDescription,
  FieldError,
  FieldContent,
} from "../ui/field";

interface SettingsFieldProps {
  label: string;
  htmlFor?: string;
  description?: string;
  error?: string;
  required?: boolean;
  /** When false, the field is visually muted and disabled. */
  enabled?: boolean;
  /** Hint shown when `enabled` is false. */
  disabledHint?: string;
  /** Layout. Defaults to vertical. */
  orientation?: "vertical" | "horizontal" | "responsive";
  className?: string;
  children: React.ReactNode;
}

/**
 * Unified settings field wrapper. Adds a "disabled because X is off" state
 * (muted + disabled + hint) on top of the base `Field` primitive, so dependent
 * fields read as "inactive" instead of looking broken.
 */
export function SettingsField({
  label,
  htmlFor,
  description,
  error,
  required,
  enabled = true,
  disabledHint,
  orientation = "vertical",
  className,
  children,
}: SettingsFieldProps) {
  return (
    <Field orientation={orientation} className={cn(!enabled && "opacity-50", className)}>
      <FieldContent>
        <FieldLabel htmlFor={htmlFor}>
          {label}
          {required && <span className="text-destructive ml-1">*</span>}
        </FieldLabel>
        {description && <FieldDescription>{description}</FieldDescription>}
        {!enabled && disabledHint && (
          <FieldDescription className="text-warning dark:text-warning">
            {disabledHint}
          </FieldDescription>
        )}
        {error && <FieldError>{error}</FieldError>}
      </FieldContent>
      <div className={cn("w-full", !enabled && "pointer-events-none")}>{children}</div>
    </Field>
  );
}

export default SettingsField;
