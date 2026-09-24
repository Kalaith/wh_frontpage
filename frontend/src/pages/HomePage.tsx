import React, { useEffect, useState } from 'react';
import { ProjectShowcase } from '../components/ProjectShowcase';
import { ArchiveSection } from '../components/ArchiveSection';
import { Footer } from '../components/Footer';
import ProjectHealthDashboard from '../components/ProjectHealthDashboard';
import { LatestUpdates } from '../components/LatestUpdates';
import { useHomepageProjects } from '../hooks/useProjectsQuery';

const HomePage: React.FC = () => {
  const [showHealth, setShowHealth] = useState(false);
  const {
    data: projectsData,
    isLoading: loading,
    error,
    refetch,
    isFetching,
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
            'floating-chat.donateButton.background-color': '#277C72',
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

  return (
    <div className="hatchery-home">
      <div className="hatchery-container">
        {error ? (
          <section id="explore" className="hatchery-empty" role="status">
            <h2>The collection couldn’t be loaded.</h2>
            <p>Please try again in a moment.</p>
            <button
              type="button"
              className="hatchery-button"
              disabled={isFetching}
              onClick={() => void refetch()}
            >
              {isFetching ? 'Trying again…' : 'Try again'}
            </button>
          </section>
        ) : (
          <ProjectShowcase
            data={projectsData}
            isLoading={loading}
            recentUpdates={<LatestUpdates />}
          />
        )}
        <div className="hatchery-growing-note">
          <span aria-hidden="true">✳</span>
          <p>
            <strong>A hatchery, always in progress.</strong> Some ideas are just
            taking shape. Others are ready to play. Have a look around.
          </p>
          <a href="/project_roost/">
            Visit Project Roost <span aria-hidden="true">↗</span>
          </a>
        </div>
        <ArchiveSection projects={projectsData?.archived} isLoading={loading} />
        <details
          className="hatchery-health"
          onToggle={event => setShowHealth(event.currentTarget.open)}
        >
          <summary>
            Behind the scenes <span>Ecosystem health</span>
          </summary>
          {showHealth && <ProjectHealthDashboard />}
        </details>
        <Footer data={projectsData} isLoading={loading} />
      </div>
    </div>
  );
};

export default HomePage;
