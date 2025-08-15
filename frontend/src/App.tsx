import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import HomePage from './pages/HomePage';
import ProjectsPage from './pages/ProjectsPage';
import './styles/global.css'; // Ensure global styles are imported

const App: React.FC = () => {
  // Use Vite's BASE_URL if available so the router knows it's mounted at /frontpage/
  const basename = ((import.meta as any).env?.BASE_URL as string) || '/frontpage/';

  return (
    <AuthProvider>
      <BrowserRouter basename={basename}>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/projects" element={<ProjectsPage />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
};

export default App;