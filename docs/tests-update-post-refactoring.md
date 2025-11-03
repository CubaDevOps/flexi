# Tests Update After Core Refactoring

## Overview
This document describes the test updates made after removing the circular dependency between the core and Session module, and implementing the module filtering system.

## Changes Made

### 1. RouterTest Updates (`tests/Infrastructure/Http/RouterTest.php`)

#### Removed Session Dependency
- **Removed Import**: `SessionStorageInterface` import
- **Removed Property**: `private SessionStorageInterface $session;`
- **Updated Property Type**: Changed `private Router $router;` to `private RouterMock $router;` to properly access spy properties
- **Updated setUp()**: Removed session mock creation and parameter from Router instantiation
- **Updated Constructor Calls**: All Router/RouterMock instantiations changed from 5 parameters to 4:
  ```php
  // Before
  new Router($session, $event_bus, $class_factory, $response_factory, $container)

  // After
  new Router($event_bus, $class_factory, $response_factory, $container)
  ```

#### Impact
- All router tests continue to pass
- Tests properly verify router functionality without session dependency
- RouterMock spy properties (`route_counter`, `redirect_to_not_found_spy`) work correctly

### 2. RouterMock Updates (`tests/TestData/TestDoubles/RouterMock.php`)

#### Method Rename
- **Changed Method**: `redirectToNotFound()` → `handleNotFound()`
- **Reason**: Router now uses event-driven approach with `handleNotFound()` that dispatches `RouteNotFoundEvent`
- **Maintained Functionality**: Spy property `redirect_to_not_found_spy` still tracks when 404 handling occurs

```php
// Before
public function redirectToNotFound(
    ServerRequestInterface $request,
    string $previous_route
): ResponseInterface {
    $response = $this->response_factory->createResponse(404);
    $this->redirect_to_not_found_spy = true;
    return $response;
}

// After
public function handleNotFound(
    ServerRequestInterface $request,
    string $previous_route
): ResponseInterface {
    $response = $this->response_factory->createResponse(404);
    $this->redirect_to_not_found_spy = true;
    return $response;
}
```

### 3. EventBusTest Updates (`tests/Infrastructure/Bus/EventBusTest.php`)

#### Removed Module Dependencies
- **Problem**: Test was importing and referencing `LoggerEventListener` from Logging module (not installed as Composer package)
- **Solution**: Changed to use generic test listener instead of module-specific class

#### Changes Made
1. **Removed Imports**:
   - `CubaDevOps\Flexi\Modules\Logging\Application\EventListeners\LoggerEventListener`
   - `CubaDevOps\Flexi\Contracts\Interfaces\LogRepositoryInterface`

2. **Added Import**:
   - `CubaDevOps\Flexi\Contracts\Interfaces\EventListenerInterface`

3. **Added Test Constant**:
   ```php
   private const TEST_LISTENER_CLASS = 'TestEventListener';
   ```

4. **Updated setUp()**:
   ```php
   // Before
   $this->eventBus->loadHandlersFromJsonFile('./src/Config/listeners.json');

   // After
   $this->eventBus->register('test.event', self::TEST_LISTENER_CLASS);
   ```

5. **Updated Test Methods**:
   - Changed event name from `'*'` to `'test.event'`
   - Changed class reference from `LoggerEventListener::class` to `self::TEST_LISTENER_CLASS`
   - Mock now uses `EventListenerInterface` instead of specific listener class

6. **Added New Test**:
   ```php
   public function testHasNoHandler(): void
   {
       $this->assertFalse($this->eventBus->hasHandler('non.existent.event'));
   }
   ```

#### Benefits
- Tests no longer depend on installed modules
- Tests verify EventBus behavior independent of module availability
- Module filtering is properly respected
- Added test coverage for non-existent handlers

## Test Results

### Before Updates
- **Status**: 2 failures, 1 error
- **Issues**:
  - EventBusTest trying to use uninstalled module class
  - RouterTest had session dependency

### After Updates
- **Status**: All tests passing ✅
- **Stats**: 122 tests, 254 assertions
- **Time**: 00:00.298, Memory: 12.00 MB

## Key Learnings

1. **Test Doubles Must Stay Synchronized**: When changing production code signatures, test doubles (mocks, spies, stubs) must be updated immediately

2. **Module Filtering Applies to Tests**: Tests should not hardcode dependencies on specific modules unless those modules are guaranteed to be installed via Composer

3. **Type Hints in Tests**: Proper type hints for test properties (e.g., `RouterMock` instead of `Router`) enable access to spy/mock-specific properties

4. **Generic Test Fixtures**: Using generic test constants and interfaces makes tests more resilient to module changes

## Files Modified

1. `/tests/Infrastructure/Http/RouterTest.php` - Removed session dependency, fixed property type
2. `/tests/TestData/TestDoubles/RouterMock.php` - Renamed method to match Router changes
3. `/tests/Infrastructure/Bus/EventBusTest.php` - Removed module-specific dependencies

## Related Documentation

- [Core Module Dependency Removal](./core-module-dependency-removal.md)
- [Router Installed Modules Filtering](./router-installed-modules-filtering.md)
- [Installed Modules Filter Centralization](./installed-modules-filter-centralization.md)
