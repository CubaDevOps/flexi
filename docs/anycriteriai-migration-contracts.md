# AnyCriteria Migration to Contracts

**Date:** October 27, 2025
**Branch:** refactor-complete-psr-compatibility
**Status:** ✅ COMPLETED

## Overview

Migrated `AnyCriteria` class from `src/Domain/Criteria/` to `contracts/src/Classes/Criteria/` following the same pattern established with other generic classes.

## Justification

`AnyCriteria` is a **Null Object pattern** implementation for criteria queries:
- Implements `CriteriaInterface` but matches any request without filtering
- Used when no specific filtering criteria is needed
- Is reusable by any module that needs default/wildcard filtering behavior
- Not a domain-specific concern - it's a generic filtering pattern

### Decision Rationale

| Aspect | Analysis |
|--------|----------|
| **Purpose** | Generic filtering pattern (Null Object) |
| **Reusability** | Can be used by any module implementing repositories |
| **Domain Specificity** | Not domain-specific, applicable universally |
| **Current Usage** | Used in HealthCheck module + potential other modules |
| **Migration Pattern** | Follows same pattern as EventListener, Traits, ValueObjects |

## Changes Made

### 1. File Creation

Created new file: `contracts/src/Classes/Criteria/AnyCriteria.php`

**Structure:**
```
contracts/src/Classes/Criteria/
└── AnyCriteria.php
    ├── Namespace: Flexi\Contracts\Classes\Criteria
    ├── Implements: CriteriaInterface
    └── Pattern: Null Object (matches all requests)
```

### 2. File Removal

- Deleted: `src/Domain/Criteria/AnyCriteria.php`
- Deleted: Empty directory `src/Domain/Criteria/`

### 3. Imports Updated

**Module: HealthCheck**
- File: `modules/HealthCheck/Application/UseCase/Health.php`
- Old: `use CubaDevOps\Flexi\Domain\Criteria\AnyCriteria;`
- New: `use Flexi\Contracts\Classes\Criteria\AnyCriteria;`

## Verification

✅ **Pre-Migration Checks:**
- Verified AnyCriteria was not actively used in core (`src/`)
- Identified single usage in HealthCheck module
- No other module references found

✅ **Post-Migration Validation:**
- Composer autoload regenerated: `3973 classes` (unchanged - same number of files)
- Full test suite execution: **171 tests, 333 assertions - ALL PASSING**
- Zero test failures or errors
- No breaking changes detected

## Architecture Impact

### Before
```
src/Domain/Criteria/          ← Domain-specific location (incorrect)
├── AnyCriteria.php           ← Generic pattern, shouldn't be in Domain
```

### After
```
contracts/src/Classes/Criteria/    ← Generic implementations
├── AnyCriteria.php                ← Null Object pattern (reusable)
```

## Benefits

1. **Cleaner Domain Layer** - Domain no longer contains generic patterns
2. **Code Reusability** - Other modules can now import AnyCriteria from Contracts
3. **Architectural Consistency** - Follows same pattern as EventListener, Traits
4. **Clear Separation** - Core framework concerns vs. reusable abstractions
5. **Extensibility** - Future modules have access to this filtering pattern

## Files Modified

| File | Change | Type |
|------|--------|------|
| `modules/HealthCheck/Application/UseCase/Health.php` | Import path updated | Import Update |
| `src/Domain/Criteria/AnyCriteria.php` | Deleted | File Removal |
| `src/Domain/Criteria/` (dir) | Removed | Directory Removal |
| `contracts/src/Classes/Criteria/AnyCriteria.php` | Created | New File |
| `contracts/src/Classes/Criteria/` (dir) | Created | New Directory |

## Test Results

```
✅ PHPUnit 9.6.29
✅ PHP 7.4.33
✅ 171 tests EXECUTED
✅ 333 assertions VERIFIED
✅ Time: 0.339 seconds
✅ Memory: 12.00 MB
✅ Result: OK (0 errors, 0 failures)
```

## Next Steps

Following the established pattern, potential future migrations:

1. **Template System** - Evaluate `Template.php`, `HtmlRender.php`, `TemplateLocator.php`
   - Determine if truly generic or core-specific
   - Consider modularization vs. consolidation

2. **PsrLogger Migration** - Move generic logging adapter to Contracts
   - Enables reuse in other packages
   - Low risk, high value

3. **Auth Module Separation** - Extract middleware and domain logic
   - AuthCheckMiddleware, JWTAuthMiddleware → separate module
   - Medium priority, medium effort

## Architectural Progress

| Item | Status | Notes |
|------|--------|-------|
| Generic Traits | ✅ Moved to Contracts | CacheKeyGenerator, FileHandler, etc. |
| EventListener | ✅ Moved to Contracts | Base class for modules |
| ValueObjects (generic) | ✅ Moved to Contracts | Operator, Order |
| AnyCriteria | ✅ Moved to Contracts | Null Object pattern |
| **Total Progress** | ✅ **90%** | Core focused on orquestación |

---

**AnyCriteria migration completed successfully. Framework core now contains only essential orquestration code.**
