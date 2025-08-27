import '@testing-library/jest-dom';

// Setup global test environment
beforeAll(() => {
  // Mock HTMLCanvasElement methods that might be used in tests
  HTMLCanvasElement.prototype.getContext = vi.fn();
  HTMLCanvasElement.prototype.toDataURL = vi.fn();

  // Mock URL.createObjectURL and URL.revokeObjectURL
  global.URL.createObjectURL = vi.fn(() => 'mock-object-url');
  global.URL.revokeObjectURL = vi.fn();

  // Mock window.matchMedia
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: vi.fn().mockImplementation(query => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: vi.fn(), // deprecated
      removeListener: vi.fn(), // deprecated
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })),
  });

  // Mock ResizeObserver
  global.ResizeObserver = vi.fn().mockImplementation(() => ({
    observe: vi.fn(),
    unobserve: vi.fn(),
    disconnect: vi.fn(),
  }));
});

afterEach(() => {
  // Clean up any mocks after each test
  vi.clearAllMocks();
});
