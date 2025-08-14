import React from 'react';

interface BadgeProps {
  children: React.ReactNode;
  className?: string;
  variant?: 'default' | 'stage' | 'status' | 'version';
}

export const Badge: React.FC<BadgeProps> = ({ 
  children, 
  className = '', 
  variant = 'default' 
}) => {
  const baseClasses = {
    default: 'badge',
    stage: 'stage-badge',
    status: 'status-badge',
    version: 'version-badge'
  };

  return (
    <span className={`${baseClasses[variant]} ${className}`}>
      {children}
    </span>
  );
};
