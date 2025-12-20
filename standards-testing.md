# WebHatchery Testing Standards

This document covers testing standards for both frontend and backend development.

## 🧪 Testing Standards

### Frontend Testing (Vitest + Testing Library)

#### Required Testing Setup
```typescript
// ✅ CORRECT: Component testing with Testing Library
// components/ui/Button.test.tsx
import { render, screen, fireEvent } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Button } from './Button'

describe('Button', () => {
  it('renders with correct text', () => {
    render(<Button>Click me</Button>)
    expect(screen.getByRole('button', { name: /click me/i })).toBeInTheDocument()
  })

  it('calls onClick when clicked', async () => {
    const user = userEvent.setup()
    const handleClick = vi.fn()
    
    render(<Button onClick={handleClick}>Click me</Button>)
    
    await user.click(screen.getByRole('button', { name: /click me/i }))
    
    expect(handleClick).toHaveBeenCalledTimes(1)
  })

  it('is disabled when disabled prop is true', () => {
    render(<Button disabled>Click me</Button>)
    
    expect(screen.getByRole('button', { name: /click me/i })).toBeDisabled()
  })
})

// ✅ CORRECT: Custom hook testing
// hooks/useCounter.test.ts
import { renderHook, act } from '@testing-library/react'
import { useCounter } from './useCounter'

describe('useCounter', () => {
  it('starts with initial value', () => {
    const { result } = renderHook(() => useCounter(5))
    expect(result.current.count).toBe(5)
  })

  it('increments count', () => {
    const { result } = renderHook(() => useCounter(0))
    
    act(() => {
      result.current.increment()
    })
    
    expect(result.current.count).toBe(1)
  })

  it('decrements count', () => {
    const { result } = renderHook(() => useCounter(5))
    
    act(() => {
      result.current.decrement()
    })
    
    expect(result.current.count).toBe(4)
  })
})

// ✅ CORRECT: Store testing with Zustand
// stores/gameStore.test.ts
import { renderHook, act } from '@testing-library/react'
import { useGameStore } from './gameStore'

describe('useGameStore', () => {
  beforeEach(() => {
    // Reset store state before each test
    act(() => {
      useGameStore.getState().resetGame()
    })
  })

  it('starts with initial state', () => {
    const { result } = renderHook(() => useGameStore())
    
    expect(result.current.gold).toBe(0)
    expect(result.current.level).toBe(1)
  })

  it('adds gold correctly', () => {
    const { result } = renderHook(() => useGameStore())
    
    act(() => {
      result.current.addGold(100)
    })
    
    expect(result.current.gold).toBe(100)
  })
})
```

#### Testing Best Practices
- **Test File Naming**: Use `.test.tsx` or `.test.ts` suffix
- **Test Coverage**: Minimum 80% for components and hooks
- **Mock External Dependencies**: Use `vi.mock()` for API calls and external libraries
- **Test User Interactions**: Use `@testing-library/user-event` for realistic interactions
- **Avoid Testing Implementation Details**: Test behavior, not internal state
- **Use Descriptive Test Names**: Describe what the test verifies

#### Testing Directory Structure
```
src/
├── components/
│   ├── Button/
│   │   ├── Button.tsx
│   │   ├── Button.test.tsx
│   │   └── index.ts
│   └── ...
├── hooks/
│   ├── useCounter.ts
│   ├── useCounter.test.ts
│   └── ...
├── stores/
│   ├── gameStore.ts
│   ├── gameStore.test.ts
│   └── ...
└── test/
    ├── setup.ts
    ├── utils.ts
    └── mocks/
        ├── api.ts
        └── ...
```

### Backend Testing (MANDATORY)
```php
<?php
// ✅ CORRECT: PHPUnit test with proper setup
declare(strict_types=1);

namespace Tests\Actions;

use App\Actions\CreateUserAction;
use App\External\UserRepository;
use PHPUnit\Framework\TestCase;

final class CreateUserActionTest extends TestCase
{
    private CreateUserAction $action;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->action = new CreateUserAction($this->userRepository);
    }

    public function testExecuteCreatesUser(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('create')
            ->willReturn(new User());

        $result = $this->action->execute('John Doe', 'john@example.com');
        
        $this->assertInstanceOf(User::class, $result);
    }
}
```

## 📊 Code Quality Standards

### Metrics Requirements
- **TypeScript**: Strict mode enabled, no `any` types
- **PHP**: PSR-12 compliance, strict types declared
- **Test Coverage**: Minimum 80% for frontend (Vitest), 70% for backend Actions and Services
- **Linting**: Zero ESLint errors, zero PHP_CodeSniffer errors

### Documentation Requirements
- **README.md**: Setup instructions, API documentation
- **Code Comments**: Complex business logic must be documented
- **Type Definitions**: All public APIs must be fully typed
