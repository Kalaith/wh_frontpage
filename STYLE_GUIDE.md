# WebHatchery Frontpage Style Guide

## Overview

This style guide defines the consistent design patterns, component conventions, and styling rules for the WebHatchery Frontpage React application.

## Design System

### Color Palette

**Primary Colors:**
- Primary Blue: `#3e8ed0` (--primary)
- Accent Teal: `#5bc0be` (--accent)
- Blue 600: Used in Tailwind classes (`text-blue-600`, `bg-blue-600`)
- Teal 500: Used for accent text (`text-teal-500`)

**Status Colors:**
- Success: Green variants (`text-green-600`, `bg-green-50`)
- Warning: Orange variants (`text-orange-600`, `bg-orange-50`)
- Error: Red variants (`text-red-600`, `bg-red-50`)
- Info: Blue variants (consistent with primary)

**Neutral Colors:**
- Gray scale: 50, 100, 200, 300, 400, 500, 600, 700, 800, 900
- White: `#ffffff`
- Dark mode variants: Available but not consistently applied

### Typography

**Font Hierarchy:**
- H1: `text-4xl font-bold` (36px, bold)
- H2: `text-2xl font-semibold` (24px, semi-bold)
- H3: `text-xl font-semibold` (20px, semi-bold)
- H4: `text-lg font-medium` (18px, medium)
- Body: `text-base` (16px, normal)
- Small: `text-sm` (14px, normal)
- Extra Small: `text-xs` (12px, normal)

**Font Weights:**
- Normal: `font-normal` (400)
- Medium: `font-medium` (500)
- Semi-bold: `font-semibold` (600)
- Bold: `font-bold` (700)

### Spacing System

**Margins and Padding:**
- Standard spacing scale: 1, 2, 3, 4, 6, 8, 12, 16, 20, 24
- Common patterns:
  - Card padding: `p-6` (24px)
  - Section margins: `mb-12` (48px)
  - Item gaps: `gap-4` (16px), `gap-6` (24px)

**Layout Containers:**
- Max width: `max-w-6xl mx-auto` or `max-w-7xl mx-auto`
- Standard page padding: `p-8`
- Responsive padding: `px-4 sm:px-6 lg:px-8`

## Component Patterns

### Cards

**Base Card Pattern:**
```tsx
<div className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden border-l-4 border-blue-500">
  <header className="p-6 pb-4 border-b border-gray-100">
    {/* Card header content */}
  </header>
  <div className="p-6 pt-4">
    {/* Card body content */}
  </div>
  <footer className="px-6 py-4 bg-gray-50 border-t border-gray-100">
    {/* Card footer content */}
  </footer>
</div>
```

**Key Features:**
- Left border accent (`border-l-4 border-blue-500`)
- Shadow progression on hover
- Consistent padding structure
- Optional footer with background

### Buttons

**Primary Button:**
```tsx
<button className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50">
```

**Secondary Button:**
```tsx
<button className="px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 transition-colors">
```

**Link Button:**
```tsx
<a className="inline-block text-blue-600 font-medium hover:text-teal-500 hover:underline transition-colors">
```

### Navigation

**Navigation Links:**
```tsx
<Link className="px-3 py-2 rounded-md text-sm font-medium transition-colors text-gray-600 hover:text-blue-600 hover:bg-gray-50">
```

**Active State:**
```tsx
<Link className="px-3 py-2 rounded-md text-sm font-medium bg-blue-100 text-blue-700">
```

### Forms

**Input Fields:**
```tsx
<input className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
```

**Labels:**
```tsx
<label className="block text-sm font-medium text-gray-700 mb-1">
```

**Error States:**
```tsx
<div className="p-3 bg-red-50 border border-red-200 rounded-md">
  <p className="text-sm text-red-600">{error}</p>
</div>
```

### Badges

**Base Badge Structure:**
- Uses legacy CSS classes from `global.css`
- Variants: `badge`, `stage-badge`, `status-badge`, `version-badge`
- Consistent padding: `0.2rem 0.6rem`
- Rounded corners: `border-radius: 12px`
- Uppercase text with letter spacing

### Modals

**Modal Overlay:**
```tsx
<div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
```

**Modal Content:**
```tsx
<div className="bg-white rounded-lg shadow-xl max-w-md w-full">
  <div className="px-6 py-4 border-b border-gray-200">
    {/* Header */}
  </div>
  <div className="px-6 py-4">
    {/* Body */}
  </div>
</div>
```

### Grids and Layout

**Stats Grid:**
```tsx
<div className="grid grid-cols-2 gap-4 md:grid-cols-4 md:gap-6">
```

**Project Grid:**
```tsx
<div className="grid grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)) gap-2rem">
```

## Animation and Transitions

### Standard Transitions

**Default Transition:**
```css
transition: all 0.3s ease;
```

**Color Transitions:**
```tsx
className="transition-colors"
```

**Shadow Transitions:**
```tsx
className="transition-shadow"
```

### Framer Motion Patterns

**Modal Animations:**
```tsx
initial={{ opacity: 0, scale: 0.95, y: 20 }}
animate={{ opacity: 1, scale: 1, y: 0 }}
exit={{ opacity: 0, scale: 0.95, y: 20 }}
```

**Hover Effects:**
- Cards: `hover:shadow-md` or `hover:-translate-y-0.5`
- Buttons: Background color transitions
- Links: Color and underline transitions

## Responsive Design

### Breakpoints
- Mobile: Base styles (default)
- Tablet: `md:` prefix (768px and up)
- Desktop: `lg:` prefix (1024px and up)

### Common Patterns

**Navigation:**
- Desktop: Horizontal navigation
- Mobile: Dropdown select or hamburger menu

**Grids:**
- Desktop: Multi-column layouts
- Mobile: Single column with `grid-cols-1`

**Spacing:**
- Responsive padding: `px-4 sm:px-6 lg:px-8`
- Responsive gaps: `gap-4 md:gap-6`

## Code Standards

### TypeScript

**Interface Naming:**
- Props interfaces: `ComponentNameProps`
- Data models: `PascalCase` (e.g., `Project`, `FeatureRequest`)

**Component Structure:**
```tsx
interface ComponentProps {
  // Props definition
}

export const Component: React.FC<ComponentProps> = ({ props }) => {
  // Component logic
  return (
    // JSX
  );
};
```

### CSS Classes

**Order of Classes:**
1. Layout (display, position, flex, grid)
2. Sizing (width, height, padding, margin)
3. Typography (font, text color, text size)
4. Background and borders
5. Visual effects (shadow, opacity)
6. State modifiers (hover, focus)
7. Responsive variants

**Example:**
```tsx
className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md shadow-sm hover:bg-blue-700 transition-colors"
```

## Legacy CSS Integration

### Custom CSS Variables

The application uses CSS custom properties for consistent theming:
```css
:root {
  --primary: #3e8ed0;
  --accent: #5bc0be;
  --transition: all 0.3s ease;
}
```

### Legacy Class Usage

When using legacy classes from `global.css`:
- Badge components use legacy badge classes
- Project cards may use legacy project styling
- Navigation uses modern Tailwind classes

## Dark Mode Support

**Current State:**
- Partial dark mode support in some components
- `dark:` prefixed classes used inconsistently
- Dark mode toggle not fully implemented

**Best Practices:**
- Always provide dark mode variants for new components
- Use semantic color names rather than specific colors
- Test components in both light and dark modes

## Accessibility

### Focus Management

**Focus Indicators:**
```tsx
className="focus:outline-none focus:ring-2 focus:ring-blue-500"
```

**Interactive Elements:**
- All buttons and links should have visible focus states
- Form inputs should have clear focus rings
- Modal focus should be trapped within the modal

### Semantic HTML

- Use proper heading hierarchy
- Form labels should be associated with inputs
- Use semantic HTML elements (nav, main, article, etc.)

### Screen Reader Support

- Provide `aria-label` for icon buttons
- Use `alt` text for decorative images
- Maintain logical tab order

## Performance Guidelines

### Bundle Size

- Use dynamic imports for large components
- Lazy load routes and modals
- Minimize CSS bundle size by using Tailwind's purge feature

### Image Optimization

- Use appropriate image formats
- Implement lazy loading for images
- Provide responsive image variants

### Animation Performance

- Use CSS transforms over position changes
- Prefer opacity transitions over display changes
- Use `will-change` sparingly and remove after animations

## Testing Guidelines

### Component Testing

- Test user interactions (clicks, form submissions)
- Test different prop combinations
- Test responsive behavior
- Test accessibility features

### Visual Regression

- Screenshot test key components
- Test color contrast ratios
- Validate responsive layouts

## Future Considerations

### Design System Evolution

- Implement a design token system
- Create reusable design components library
- Establish consistent icon system
- Improve dark mode implementation

### Performance Optimizations

- Implement CSS-in-JS for better code splitting
- Consider using a CSS framework optimized for bundle size
- Optimize animation performance with hardware acceleration

### Accessibility Improvements

- Implement comprehensive keyboard navigation
- Add high contrast mode support
- Improve screen reader announcements
- Add focus management for single-page application routing