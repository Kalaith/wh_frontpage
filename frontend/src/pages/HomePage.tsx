import React, { useEffect } from 'react';
import { ProjectLegend } from '../components/ProjectLegend';
import { ProjectShowcase } from '../components/ProjectShowcase';
import { Footer } from '../components/Footer';
import ProjectHealthDashboard from '../components/ProjectHealthDashboard';
import { useHomepageProjects } from '../hooks/useProjectsQuery';
import { SeasonBanner } from '../components/SeasonBanner';
import WeeklyHeist from '../components/WeeklyHeist';

const HomePage: React.FC = () => {
  const {
    data: projectsData,
    isLoading: loading,
    error,
  } = useHomepageProjects();

  // Inject Ko-fi overlay widget
  useEffect(() => {
    const scriptSrc = 'https://storage.ko-fi.com/cdn/scripts/overlay-widget.js';
    let scriptEl: HTMLScriptElement | null = document.querySelector(
      `script[src="${scriptSrc}"]`
    );

    const initWidget = () => {
      try {
        const kofi = (
          window as {
            kofiWidgetOverlay?: {
              draw?: (id: string, config: Record<string, string>) => void;
            };
          }
        ).kofiWidgetOverlay;
        if (kofi && typeof kofi.draw === 'function') {
          kofi.draw('webhatchery', {
            type: 'floating-chat',
            'floating-chat.donateButton.text': 'Support me',
            'floating-chat.donateButton.background-color': '#00b9fe',
            'floating-chat.donateButton.text-color': '#fff',
          });
        }
      } catch {
        // non-fatal
        // console.warn('Ko-fi widget init failed');
      }
    };

    if (!scriptEl) {
      scriptEl = document.createElement('script');
      scriptEl.src = scriptSrc;
      scriptEl.async = true;
      scriptEl.onload = initWidget;
      document.body.appendChild(scriptEl);
    } else {
      // Script already present, try to initialize immediately
      initWidget();
    }

    return () => {
      // Do not forcibly remove the script if other pages may rely on it; just attempt to remove our onload
      if (scriptEl && scriptEl.onload === initWidget) {
        scriptEl.onload = null;
      }
    };
  }, []);

  if (error) {
    return (
      <div className="max-w-6xl mx-auto p-8">
        <div className="text-center py-8">
          <p className="text-lg text-red-600">
            Error: {error?.message ?? 'Failed to load projects data'}
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="w-full mx-auto px-4 md:px-8 lg:px-12 py-8">
      <div className="flex flex-col xl:flex-row gap-8 lg:gap-12">
        {/* Main Content Area (Left side on XL screens) */}
        <div className="flex-1 min-w-0">
          <p className="text-center text-lg text-slate-700 max-w-3xl mx-auto mb-10">
            Web Hatchery is a collection of vibe-coded prototypes built to
            quickly explore new ideas, with quests to improve each prototype.
          </p>

          <div className="mb-8">
            <ProjectHealthDashboard />
          </div>

          {/* Project Portfolio - Full Width within main area */}
          <ProjectShowcase data={projectsData} isLoading={loading} />

          <Footer data={projectsData} isLoading={loading} />
        </div>

        {/* Sidebar Area (Right side on XL screens) */}
        <div className="w-full xl:w-[350px] shrink-0 space-y-6">
          <SeasonBanner />
          <ProjectLegend />
          <WeeklyHeist />
        </div>
      </div>
    </div>
  );
};

export default HomePage;
