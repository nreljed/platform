import * as React from "react";
import { cn } from "../../lib/utils";
import { Button } from "../ui/button";
import { Alert, AlertDescription, AlertTitle } from "../ui/alert";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "../ui/alert-dialog";
import { AlertTriangle, Trash2 } from "lucide-react";

interface DangerZoneAction {
  label: string;
  description: string;
  confirmLabel?: string;
  /** When set, the user must type this string to confirm. */
  confirmText?: string;
  variant?: "destructive" | "outline";
  onConfirm: () => void | Promise<void>;
}

interface DangerZoneProps {
  title?: string;
  description?: string;
  actions: DangerZoneAction[];
  className?: string;
}

/**
 * Standardised destructive-actions block. Uses the AlertDialog primitive with
 * optional type-to-confirm for high-impact operations, keeping the visual
 * treatment consistent across admin pages.
 */
export function DangerZone({
  title = "Danger zone",
  description = "These actions are destructive and cannot be undone.",
  actions,
  className,
}: DangerZoneProps) {
  return (
    <div
      className={cn(
        "rounded-xl border border-destructive/30 bg-destructive/5 p-4 space-y-4",
        className,
      )}
    >
      <div className="flex items-start gap-3">
        <AlertTriangle className="h-5 w-5 text-destructive shrink-0 mt-0.5" />
        <div className="space-y-0.5">
          <p className="font-medium text-destructive">{title}</p>
          <p className="text-sm text-muted-foreground">{description}</p>
        </div>
      </div>
      <div className="space-y-3">
        {actions.map((action, i) => (
          <DangerAction key={i} action={action} />
        ))}
      </div>
    </div>
  );
}

function DangerAction({ action }: { action: DangerZoneAction }) {
  const [typed, setTyped] = React.useState("");
  const needsConfirm = !!action.confirmText;
  const canConfirm = !needsConfirm || typed === action.confirmText;

  return (
    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-lg border border-border bg-card p-3">
      <div className="space-y-0.5 min-w-0">
        <p className="text-sm font-medium text-foreground">{action.label}</p>
        <p className="text-xs text-muted-foreground">{action.description}</p>
      </div>
      <AlertDialog>
        <AlertDialogTrigger
          render={
            <Button variant={action.variant ?? "destructive"} size="sm">
              <Trash2 className="h-3.5 w-3.5" />
              {action.label}
            </Button>
          }
        />
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{action.label}?</AlertDialogTitle>
            <AlertDialogDescription>
              {action.description}
              {needsConfirm && (
                <span className="block mt-3 text-foreground">
                  Type <code className="font-mono text-destructive">{action.confirmText}</code> to confirm.
                </span>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          {needsConfirm && (
            <input
              value={typed}
              onChange={(e) => setTyped(e.target.value)}
              placeholder={action.confirmText}
              className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground"
            />
          )}
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              variant="destructive"
              disabled={!canConfirm}
              onClick={() => action.onConfirm()}
            >
              {action.confirmLabel ?? action.label}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

export default DangerZone;
