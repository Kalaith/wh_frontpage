import React, { useEffect } from 'react';
import { QuickLinks } from '../components/QuickLinks';
import { ProjectLegend } from '../components/ProjectLegend';
import { ProjectShowcase } from '../components/ProjectShowcase';
import { Footer } from '../components/Footer';
import ProjectUpdates from '../components/ProjectUpdates';
import ProjectHealthDashboard from '../components/ProjectHealthDashboard';
import { useHomepageProjects } from '../hooks/useProjectsQuery';

const HomePage: React.FC = () => {
  const { data: projectsData, isLoading: loading, error } = useHomepageProjects();

  // Inject Ko-fi overlay widget
  useEffect(() => {
    const scriptSrc = 'https://storage.ko-fi.com/cdn/scripts/overlay-widget.js';
    let scriptEl: HTMLScriptElement | null = document.querySelector(
      `script[src="${scriptSrc}"]`
    );

    const initWidget = () => {
      try {
        const kofi = (window as { kofiWidgetOverlay?: { draw?: (id: string, config: Record<string, string>) => void } }).kofiWidgetOverlay;
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
          <p className="text-lg text-red-600">Error: {error?.message ?? 'Failed to load projects data'}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto p-8">
      <QuickLinks data={projectsData} isLoading={loading} />

      {/* Top Content Grid - Status Guide and Updates side by side */}
      <div className="grid grid-cols-1 xl:grid-cols-4 gap-8 mb-8">
        {/* Left Column - Status Guide */}
        <div className="xl:col-span-2">
          <ProjectLegend />
        </div>

        {/* Right Column - Updates and Health Dashboard */}
        <div className="xl:col-span-2 space-y-8">
          <ProjectUpdates />
          <ProjectHealthDashboard />
        </div>
      </div>

      {/* Project Portfolio - Full Width */}
      <ProjectShowcase data={projectsData} isLoading={loading} />

      <Footer data={projectsData} isLoading={loading} />
    </div>
  );
};

export default HomePage;
