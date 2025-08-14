import React, { useState, useEffect } from 'react';
import type { ProjectsData } from '../types/projects';
import { ProjectsService } from '../services/projectsService';
import { Header } from '../components/Header';
import { QuickLinks } from '../components/QuickLinks';
import { ProjectLegend } from '../components/ProjectLegend';
import { ProjectShowcase } from '../components/ProjectShowcase';
import { Footer } from '../components/Footer';

const HomePage: React.FC = () => {
  const [projectsData, setProjectsData] = useState<ProjectsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

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
      <Header data={projectsData} />
      <QuickLinks data={projectsData} />
      <ProjectLegend />
      <ProjectShowcase data={projectsData} />
      <Footer data={projectsData} />
    </div>
  );
};

export default HomePage;
