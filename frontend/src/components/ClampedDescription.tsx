import React, { useLayoutEffect, useRef, useState } from 'react';

interface ClampedDescriptionProps {
  text?: string;
  className?: string;
}

/**
 * Project description that shows its full text. If a height clamp is ever
 * reintroduced on the paragraph, hovering reveals the full text in a
 * tooltip — the tooltip only renders when the text is actually truncated.
 */
export const ClampedDescription: React.FC<ClampedDescriptionProps> = ({
  text,
  className = 'text-slate-600',
}) => {
  const textRef = useRef<HTMLDivElement>(null);
  const [isClamped, setIsClamped] = useState(false);

  useLayoutEffect(() => {
    const el = textRef.current;
    if (!el) return;
    const check = () => setIsClamped(el.scrollHeight > el.clientHeight + 1);
    check();
    const observer = new ResizeObserver(check);
    observer.observe(el);
    return () => observer.disconnect();
  }, [text]);

  if (!text) {
    return null;
  }

  return (
    <div className="group/desc relative mt-1">
      {/* a div, not a p: global.css has an unlayered legacy `p` rule
          (font-size/margin) that would override these utilities */}
      <div
        ref={textRef}
        className={`text-sm leading-5 break-words ${className}`}
      >
        {text}
      </div>
      {isClamped && (
        <div
          role="tooltip"
          className="pointer-events-none invisible absolute inset-x-0 top-full z-20 mt-2 rounded-lg bg-slate-900/95 p-3 text-sm leading-5 text-slate-100 opacity-0 shadow-xl transition-opacity duration-150 group-hover/desc:visible group-hover/desc:opacity-100"
        >
          {text}
        </div>
      )}
    </div>
  );
};
