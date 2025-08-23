import React, { useState, useEffect } from 'react';
import type { ProjectsData } from '../types/projects';
import { ProjectsService } from '../services/projectsService';
import { Header } from '../components/Header';
import { QuickLinks } from '../components/QuickLinks';
import { ProjectLegend } from '../components/ProjectLegend';
import { ProjectShowcase } from '../components/ProjectShowcase';
import { Footer } from '../components/Footer';
import AuthStatus from '../components/AuthStatus';

const HomePage: React.FC = () => {
  const [projectsData, setProjectsData] = useState<ProjectsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  // auth status handled by AuthStatus component

  useEffect(() => {
    const loadProjectsData = async () => {
      try {
        const projectsService = ProjectsService.getInstance();
        const data = await projectsService.getProjectsData();
        setProjectsData(data);
      } catch (err) {
        setError(err instanceof Error ? err.message : 'Failed to load projects data');
      } finally {
        setLoading(false);
      }
    };

    loadProjectsData();
  }, []);

  // Inject Ko-fi overlay widget
  useEffect(() => {
    const scriptSrc = 'https://storage.ko-fi.com/cdn/scripts/overlay-widget.js';
    let scriptEl: HTMLScriptElement | null = document.querySelector(`script[src="${scriptSrc}"]`);

    const initWidget = () => {
      try {
        const kofi = (window as any).kofiWidgetOverlay;
        if (kofi && typeof kofi.draw === 'function') {
          kofi.draw('webhatchery', {
            type: 'floating-chat',
            'floating-chat.donateButton.text': 'Support me',
            'floating-chat.donateButton.background-color': '#00b9fe',
            'floating-chat.donateButton.text-color': '#fff'
          });
        }
      } catch (e) {
        // non-fatal
        // console.warn('Ko-fi widget init failed', e);
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

  if (loading) {
    return (
      <div className="container">
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <p>Loading WebHatchery projects...</p>
        </div>
      </div>
    );
  }

  if (error || !projectsData) {
    return (
      <div className="container">
        <div style={{ textAlign: 'center', padding: '2rem' }}>
          <p>Error: {error || 'Failed to load projects data'}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="container">
      {/* Top-right login status */}
      <div style={{ position: 'absolute', top: 12, right: 12 }}>
        <AuthStatus />
      </div>

      <Header data={projectsData} />
      <QuickLinks data={projectsData} />
      <ProjectLegend />
      <ProjectShowcase data={projectsData} />
      <Footer data={projectsData} />
    </div>
  );
};

export default HomePage;
