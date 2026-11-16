import { useState } from "react";
import { router } from "@inertiajs/react";
import { toast } from "../../ui/toast";
import { Section, SectionField } from "../../shared/section";
import { Switch } from "../../ui/switch";
import { Label } from "../../ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "../../ui/select";
import { Bell } from "lucide-react";
import { StickySaveBar } from "../StickySaveBar";
import { deepEqual } from "../../../hooks/use-settings-form";

interface NotificationSectionProps {
  settings: Record<string, string>;
  asAccordion?: boolean;
}

export function NotificationSection({ settings, asAccordion }: NotificationSectionProps) {
  const buildInitial = () => ({
    default_notification_channel: settings.default_notification_channel ?? "in_app",
    email_notifications_enabled: settings.email_notifications_enabled ?? "1",
  });
  const [data, setData] = useState(buildInitial);
  const [saved, setSaved] = useState(buildInitial);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const dirty = !deepEqual(data, saved);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (loading) return;
    setLoading(true);
    router.put(route("dashboard.admin.settings.update"), data, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        setSaved(data);
        toast.add({ title: "Notification settings saved.", type: "success" });
        setLoading(false);
      },
      onError: (errs) => {
        setErrors(errs);
        toast.add({ title: "Failed to save.", type: "error" });
        setLoading(false);
      },
    });
  };

  const discard = () => {
    setData(saved);
    setErrors({});
  };

  return (
    <>
      <Section
        title="Notifications"
      description="Configure notification defaults for new users."
      icon={Bell}
      onSubmit={handleSubmit}
      loading={loading}
      fieldErrors={errors as Record<string, string[]>}
      submitLabel="Save Changes"
      asAccordion={asAccordion}
    >
      <div className="rounded-md bg-muted p-3 text-sm text-muted-foreground">
        These are default settings applied to new users. Existing users can override these in their{" "}
        <span className="font-medium text-foreground">Account → Notifications</span> preferences.
      </div>
      <SectionField label="Default Notification Channel" htmlFor="default_notification_channel">
        <Select
          value={data.default_notification_channel}
          onValueChange={(v) => setData((p) => ({ ...p, default_notification_channel: v ?? "" }))}
        >
          <SelectTrigger id="default_notification_channel">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="in_app">In-app only</SelectItem>
            <SelectItem value="email">Email only</SelectItem>
            <SelectItem value="in_app,email">Both (in-app + email)</SelectItem>
          </SelectContent>
        </Select>
      </SectionField>
      <SectionField label="Enable Email Notifications" htmlFor="email_notifications_enabled">
        <div className="flex items-center gap-2">
          <Switch
            id="email_notifications_enabled"
            checked={data.email_notifications_enabled === "1"}
            onCheckedChange={(c) =>
              setData((p) => ({ ...p, email_notifications_enabled: c ? "1" : "0" }))
            }
          />
          <Label htmlFor="email_notifications_enabled" className="cursor-pointer font-normal">
            {data.email_notifications_enabled === "1" ? "Enabled" : "Disabled"}
          </Label>
        </div>
      </SectionField>
    </Section>
      <StickySaveBar
        dirty={dirty}
        processing={loading}
        onSave={() => handleSubmit({ preventDefault: () => {} } as React.FormEvent)}
        onDiscard={discard}
        saveLabel="Save notifications"
      />
    </>
  );
}
