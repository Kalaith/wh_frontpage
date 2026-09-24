import React from 'react';

export const HatchlingMark: React.FC<{ className?: string }> = ({
  className,
}) => (
  <svg className={className} viewBox="0 0 80 88" fill="none" aria-hidden="true">
    <path d="M19 49C16 28 26 12 41 12c16 0 25 17 22 37" fill="#E7A83E" />
    <path
      d="m37 14 1-8 6 7M29 18l-3-7 9 4"
      stroke="#E7A83E"
      strokeWidth="4"
      strokeLinecap="round"
    />
    <circle cx="47" cy="29" r="3" fill="#242A2E" />
    <path d="m59 32 13 6-13 5" fill="#C67D28" />
    <path
      d="M26 36c-1 9 5 14 12 12"
      stroke="#C67D28"
      strokeWidth="3"
      strokeLinecap="round"
    />
    <path
      d="m12 44 12 8 9-9 10 11 12-9 13 1c2 23-9 34-28 34S10 68 12 44Z"
      fill="#FFFDF8"
      stroke="#DDD8CC"
      strokeWidth="2"
    />
    <path
      d="m19 69 9 4m23 0 7-4"
      stroke="#DDD8CC"
      strokeWidth="2"
      strokeLinecap="round"
    />
  </svg>
);
