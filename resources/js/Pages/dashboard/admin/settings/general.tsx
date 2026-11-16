import { useState } from "react";
import { Head, Link } from "@inertiajs/react";
import AdminLayout from "../../../../Layouts/AdminLayout";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "../../../../Components/ui/breadcrumb";
import { AdminSettingsNav } from "../../../../Components/settings/AdminSettingsNav";
import { GeneralSection } from "../../../../Components/settings/sections/GeneralSection";
import { FormattingSection } from "../../../../Components/settings/sections/FormattingSection";
import { DateFormatSection } from "../../../../Components/settings/sections/DateFormatSection";
import { StickySaveBar } from "../../../../Components/settings/StickySaveBar";
import { SectionSaveIcon } from "../../../../Components/settings/SectionSaveIcon";
import { PreviewCard } from "../../../../Components/settings/PreviewCard";
import { useSettingsForm } from "../../../../hooks/use-settings-form";
import { useFormatting } from "../../../../hooks/use-formatting";

interface GeneralProps {
  settings?: Record<string, string>;
}

export default function GeneralSettings({ settings = {} }: GeneralProps) {
  const genForm = useSettingsForm<Record<string, unknown>>({
    initial: settings as Record<string, unknown>,
    submitUrl: route("dashboard.admin.settings.update"),
    successMessage: "General settings saved.",
  });

  const fmtForm = useSettingsForm<Record<string, unknown>>({
    initial: settings as Record<string, unknown>,
    submitUrl: route("dashboard.admin.settings.update"),
    successMessage: "Formatting settings saved.",
  });

  // Split the shared settings object: general keys vs formatting keys.
  // Both forms share the same source, so we keep them in sync via onChange.
  const [genData, setGenData] = useState(settings);
  const [fmtData, setFmtData] = useState(settings);

  // Keep useSettingsForm values in sync with local state used by sections.
  const handleGenChange = (field: string, value: string) => {
    setGenData((p) => ({ ...p, [field]: value }));
    genForm.setField(field, value);
  };
  const handleFmtChange = (field: string, value: string) => {
    setFmtData((p) => ({ ...p, [field]: value }));
    fmtForm.setField(field, value);
  };

  const combinedDirty = genForm.isDirty || fmtForm.isDirty;
  const combinedProcessing = genForm.processing || fmtForm.processing;

  const handleSaveAll = () => {
    if (genForm.isDirty) genForm.submit();
    if (fmtForm.isDirty) fmtForm.submit();
  };
  const handleDiscardAll = () => {
    genForm.discard();
    fmtForm.discard();
    setGenData(settings);
    setFmtData(settings);
  };

  const fmt = useFormatting();

  return (
    <AdminLayout title="Settings">
      <Head title="General Settings" />
      <div className="space-y-6">
        <Breadcrumb>
          <BreadcrumbList>
            <BreadcrumbItem>
              <Link
                href={route("dashboard.index")}
                className="transition-colors hover:text-foreground"
              >
                Dashboard
              </Link>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbPage>General Settings</BreadcrumbPage>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>
        <div className="flex flex-col md:flex-row gap-8">
          <AdminSettingsNav />
          <div className="flex-1 min-w-0 space-y-6">
            <GeneralSection
              data={genData}
              onChange={handleGenChange}
              errors={genForm.errors}
              loading={genForm.processing}
              onSubmit={(e) => {
                e.preventDefault();
                genForm.submit();
              }}
              asAccordion
              trailing={
                <SectionSaveIcon
                  dirty={genForm.isDirty}
                  errorCount={Object.keys(genForm.errors).length}
                />
              }
            />
            <FormattingSection
              data={fmtData}
              onChange={handleFmtChange}
              errors={fmtForm.errors}
              loading={fmtForm.processing}
              onSubmit={(e) => {
                e.preventDefault();
                fmtForm.submit();
              }}
              trailing={
                <SectionSaveIcon
                  dirty={fmtForm.isDirty}
                  errorCount={Object.keys(fmtForm.errors).length}
                />
              }
            />
            <PreviewCard
              label="Live price preview"
              value={fmt.formatPrice(1234.56)}
              hint="Updates from your currency & separator settings."
            />
            <DateFormatSection settings={settings} asAccordion />
          </div>
        </div>
      </div>
      <StickySaveBar
        dirty={combinedDirty}
        processing={combinedProcessing}
        onSave={handleSaveAll}
        onDiscard={handleDiscardAll}
        description={
          genForm.isDirty && fmtForm.isDirty
            ? "General + formatting changes"
            : genForm.isDirty
              ? "General settings"
              : "Formatting settings"
        }
      />
    </AdminLayout>
  );
}
