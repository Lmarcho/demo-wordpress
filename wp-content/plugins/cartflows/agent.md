# CartFlows Plugin - AI Agent Development Guide

> **Version:** 2.1.17  
> **Last Updated:** 2025  
> **Purpose:** Comprehensive system prompt for AI agents working on CartFlows codebase  
> **Target:** AI Development Assistants (Cursor AI, Claude, GitHub Copilot, etc.)

This document serves as the definitive guide for AI agents when generating, editing, or refactoring code in the CartFlows WordPress plugin. Follow these guidelines strictly to ensure consistency, security, and maintainability.

---

## AI EXECUTION CONTRACT (MANDATORY)

This document applies equally to **Claude CLI**, **GPT-based agents**, **Cursor**, **Qoder**, and all other AI development assistants. The following execution rules are **mandatory** and **non-negotiable**:

### Execution Scope Rules
- **AI must NOT scan the full repository** - Only access files explicitly mentioned by the user
- **AI must modify ONLY files explicitly mentioned by the user** - Do not modify related or adjacent files
- **Default behavior is PATCH-ONLY** - No refactors, no rewrites, no structural changes unless explicitly requested
- **No new abstractions, patterns, or architecture changes** unless explicitly requested by the user
- **Assume existing code already passes PHPCS, PHPStan, and PHPInsights** unless specifically told otherwise

### Information Gathering Rules
- If information is missing or unclear, **ask ONE clarifying question** instead of guessing or making assumptions
- Do not proactively suggest improvements or optimizations unless explicitly asked
- Focus only on the specific task requested by the user

### Code Modification Rules
- Make minimal, surgical changes to achieve the specific goal
- Preserve existing code style, patterns, and architecture
- Do not introduce new dependencies or patterns unless required for the specific task
- Do not refactor unrelated code or improve code quality unless explicitly requested

---

## 1. PROJECT OVERVIEW

### 1.1 What is CartFlows?

CartFlows is a WordPress plugin that extends WooCommerce to create high-converting checkout pages and sales funnels. It allows users to:

- Replace default WooCommerce checkout with custom, optimized checkout pages
- Build complete sales funnels with landing pages, checkout, upsells, downsells, and thank you pages
- Integrate with popular page builders (Elementor, Bricks, Beaver Builder, Gutenberg)
- Track conversions and optimize funnels with analytics
- Automate workflows with SureTriggers integration

### 1.2 Technical Stack

- **Backend:** PHP 7.2+ (WordPress 5.8+, WooCommerce 3.0+)
- **Frontend Admin:** React 18.3+ with Redux for state management
- **Build Tools:** Webpack (@wordpress/scripts), Grunt, PostCSS, Tailwind CSS
- **Code Quality:** PHPStan (level 9), PHPCS (WordPress Coding Standards), ESLint, Prettier, Stylelint
- **Testing:** PHPUnit, Playwright, Jest (E2E)
- **Package Management:** Composer (PHP), npm (JavaScript)

### 1.3 Key Dependencies

**PHP:**
- `brainstormforce/bsf-analytics` - Analytics tracking
- `brainstormforce/astra-notices` - Admin notices
- `brainstormforce/nps-survey` - NPS survey functionality

**JavaScript:**
- React 18.3.1, React DOM 18.2.0
- Redux 4.1.0, React Redux 7.2.4
- React Router DOM 5.2.0
- @wordpress/components, @wordpress/data, @wordpress/i18n
- Tailwind CSS 3.0.12
- Various UI libraries (react-select, react-color, apexcharts, etc.)

---

## 2. FOLDER AND ARCHITECTURE RULES

### 2.1 Directory Structure

```
cartflows/
├── admin/                    # Legacy admin assets (CSS, JS, images)
├── admin-core/              # Modern admin interface
│   ├── ajax/                # AJAX handlers (PHP)
│   ├── api/                 # REST API endpoints (PHP)
│   ├── assets/              # Admin assets
│   │   ├── build/          # Compiled JS/CSS (generated)
│   │   ├── src/            # React source code
│   │   │   ├── components/ # Reusable React components
│   │   │   ├── pages/      # Page-level React components
│   │   │   ├── store/      # Redux store and reducers
│   │   │   ├── utils/      # Utility functions
│   │   │   └── fields/     # Form field components
│   │   └── images/         # Admin images
│   ├── inc/                # Admin PHP includes
│   └── views/              # PHP view templates
├── assets/                 # Frontend assets (CSS, JS, images)
├── classes/                # Core PHP classes
│   ├── fields/            # Form field classes
│   ├── importer/          # Import functionality
│   └── logger/            # Logging classes
├── compatibilities/        # Theme/plugin compatibility
│   ├── plugins/           # Plugin compatibility classes
│   └── themes/            # Theme compatibility classes
├── modules/               # Feature modules
│   ├── checkout/         # Checkout module
│   ├── flow/             # Flow management
│   ├── landing/          # Landing page module
│   ├── optin/            # Opt-in page module
│   ├── thankyou/         # Thank you page module
│   ├── gutenberg/        # Gutenberg blocks
│   ├── elementor/        # Elementor integration
│   ├── beaver-builder/   # Beaver Builder integration
│   └── bricks/            # Bricks Builder integration
├── woocommerce/          # WooCommerce template overrides
│   └── template/         # Custom WooCommerce templates
├── wizard/               # Setup wizard
├── libraries/            # Third-party libraries (composer)
├── tests/                # Test files
│   ├── e2e/              # End-to-end tests
│   ├── php/               # PHP unit tests
│   └── play/              # Playwright tests
└── vendor/               # Composer dependencies
```

### 2.2 Architecture Principles

1. **Modular Design:** Features are organized into modules (`modules/` directory)
2. **Separation of Concerns:**
   - PHP handles backend logic, data processing, WordPress hooks
   - React handles admin UI, state management, user interactions
   - CSS/SCSS handles styling (with Tailwind for admin UI)
3. **Singleton Pattern:** Core classes use singleton pattern for global access
4. **Factory Pattern:** Step types use factory pattern (`class-cartflows-step-factory.php`)
5. **Hook-Based Architecture:** Extensive use of WordPress actions and filters for extensibility

### 2.3 File Organization Rules

**PHP Files:**
- Class files: `class-{name}.php` (e.g., `class-cartflows-loader.php`)
- Interface files: `interface-{name}.php`
- Trait files: `trait-{name}.php`
- One class per file
- File name matches class name (kebab-case)

**JavaScript/React Files:**
- Components: PascalCase (e.g., `OrderBumpProduct.js`)
- Utilities: camelCase (e.g., `apiHelpers.js`)
- Pages: PascalCase (e.g., `FlowsPage.js`)
- Use `.js` extension (not `.jsx`)

**CSS/SCSS Files:**
- Component styles: Match component name (e.g., `OrderBump.scss`)
- Global styles: Descriptive names (e.g., `main-navigation.scss`)
- Use SCSS for admin-core assets

### 2.4 Module Structure

Each module in `modules/` should follow this structure:
```
modules/{module-name}/
├── classes/              # PHP classes for the module
├── assets/               # Module-specific assets (if any)
└── class-cartflows-{module}-loader.php  # Module loader
```

---

## 3. LANGUAGE-SPECIFIC RULES

### 3.1 PHP Rules

#### 3.1.1 WordPress Coding Standards

**MUST FOLLOW:**
- WordPress Coding Standards (WPCS) as defined in `phpcs.xml.dist`
- WordPress Core, Extra, Docs, and VIP-Go standards
- PHP Compatibility: PHP 5.6+ (testVersion: 5.6-)
- Use tabs for indentation (4 spaces = 1 tab)
- No trailing whitespace
- Files must end with a newline

**Class Structure:**
```php
<?php
/**
 * Class Description.
 *
 * @package CartFlows
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cartflows_Example.
 */
class Cartflows_Example {

	/**
	 * Member Variable
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Hook registration here
	}

	/**
	 * Method description.
	 *
	 * @param string $param Parameter description.
	 * @param int    $id    ID parameter.
	 * @since 1.0.0
	 * @return mixed Return description.
	 */
	public function example_method( $param, $id = 0 ) {
		// Method implementation
	}
}
```

**Key PHP Rules:**
1. **Always check ABSPATH:** Every PHP file must start with ABSPATH check
2. **Singleton Pattern:** Use `get_instance()` for singleton classes
3. **DocBlocks:** All classes, methods, and properties must have PHPDoc comments
4. **Naming:**
   - Classes: `Cartflows_{Feature}` (PascalCase with underscores)
   - Methods: `snake_case`
   - Variables: `snake_case`
   - Constants: `UPPER_SNAKE_CASE`
5. **Hooks:** Use descriptive hook names prefixed with `cartflows_`
6. **Nonces:** Always verify nonces for AJAX and form submissions
7. **Sanitization:** Always sanitize input, escape output
8. **Capabilities:** Check user capabilities before sensitive operations

**WordPress Hooks Pattern:**
```php
// Actions
add_action( 'cartflows_order_started', array( $this, 'handle_order' ), 10, 1 );

// Filters
add_filter( 'cartflows_checkout_data', array( $this, 'modify_checkout_data' ), 10, 2 );

// Custom hooks (provide context)
do_action( 'cartflows_before_checkout_render', $step_id, $flow_id );
apply_filters( 'cartflows_checkout_fields', $fields, $step_id );
```

#### 3.1.2 PHP Security Rules

**CRITICAL - MUST FOLLOW:**

1. **Input Validation:**
   ```php
   // ✅ CORRECT
   $step_id = isset( $_GET['step_id'] ) ? absint( $_GET['step_id'] ) : 0;
   $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
   
   // ❌ WRONG
   $step_id = $_GET['step_id'];
   $name = $_POST['name'];
   ```

2. **Output Escaping:**
   ```php
   // ✅ CORRECT
   echo esc_html( $title );
   echo esc_url( $url );
   echo esc_attr( $attribute );
   echo wp_kses_post( $html_content );
   
   // ❌ WRONG
   echo $title;
   echo $url;
   ```

3. **Nonce Verification:**
   ```php
   // ✅ CORRECT
   if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'action_name' ) ) {
       wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'cartflows' ) ) );
   }
   ```

4. **Capability Checks:**
   ```php
   // ✅ CORRECT
   if ( ! current_user_can( 'manage_options' ) ) {
       wp_die( esc_html__( 'You do not have permission to perform this action.', 'cartflows' ) );
   }
   ```

5. **Database Queries:**
   ```php
   // ✅ CORRECT - Use $wpdb->prepare()
   global $wpdb;
   $results = $wpdb->get_results(
       $wpdb->prepare(
           "SELECT * FROM {$wpdb->prefix}cartflows_flows WHERE id = %d",
           $flow_id
       )
   );
   
   // ❌ WRONG - Direct concatenation
   $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cartflows_flows WHERE id = $flow_id" );
   ```

6. **File Operations:**
   ```php
   // ✅ CORRECT
   $file_path = realpath( $base_path . $relative_path );
   if ( $file_path && strpos( $file_path, $base_path ) === 0 ) {
       // Safe to use
   }
   ```

#### 3.1.3 PHP Code Quality

- **PHPStan Level 9:** All code must pass PHPStan analysis (level 9)
- **Type Hints:** Use type hints for parameters and return types where possible
- **Error Handling:** Use try-catch for operations that may fail
- **Logging:** Use `wcf()->logger->log()` for debugging (not `error_log()` in production)

### 3.2 JavaScript/React Rules

#### 3.2.1 React Component Structure

**Component Template:**
```javascript
import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Component description.
 *
 * @param {Object} props Component props.
 * @param {string} props.title Component title.
 * @param {Function} props.onChange Change handler.
 * @return {JSX.Element} Component element.
 */
const ExampleComponent = ( { title, onChange } ) => {
	const [ state, setState ] = useState( '' );

	useEffect( () => {
		// Side effects
	}, [ dependencies ] );

	const handleClick = () => {
		// Handler logic
		onChange( newValue );
	};

	return (
		<div className="wcf-example-component">
			<h2>{ title }</h2>
			<button onClick={ handleClick }>
				{ __( 'Click Me', 'cartflows' ) }
			</button>
		</div>
	);
};

export default ExampleComponent;
```

**Key JavaScript/React Rules:**

1. **Imports:**
   - Use path aliases: `@Admin`, `@Utils`, `@Fields`, `@Components`, etc.
   - Group imports: React, WordPress, third-party, local
   - Use named exports when possible

2. **Internationalization:**
   ```javascript
   // ✅ CORRECT
   import { __, _x, _n, sprintf } from '@wordpress/i18n';
   
   const text = __( 'Save Changes', 'cartflows' );
   const plural = _n( '%d item', '%d items', count, 'cartflows' );
   const formatted = sprintf( __( 'Hello %s', 'cartflows' ), name );
   ```

3. **State Management:**
   - Use Redux for global state (`@SettingsApp/data/reducer`)
   - Use `useState` for local component state
   - Use `useContext` for shared state within feature areas

4. **API Calls:**
   ```javascript
   // ✅ CORRECT - Use @wordpress/api-fetch
   import apiFetch from '@wordpress/api-fetch';
   
   const response = await apiFetch( {
       path: '/cartflows/v1/flows',
       method: 'POST',
       data: { name: 'New Flow' },
   } );
   ```

5. **Event Handlers:**
   - Use descriptive names: `handleSave`, `handleInputChange`
   - Always prevent default for form submissions
   - Use arrow functions for class methods or functional components

6. **Conditional Rendering:**
   ```javascript
   // ✅ CORRECT
   { isVisible && <Component /> }
   { condition ? <ComponentA /> : <ComponentB /> }
   ```

#### 3.2.2 JavaScript Code Quality

**ESLint Configuration:**
- Follow `@wordpress/eslint-plugin/recommended-with-formatting`
- Text domain must be `'cartflows'` for i18n functions
- No console.log in production code (use proper logging)
- Camelcase is disabled (WordPress style)

**Code Style:**
- Use Prettier for formatting (WordPress Prettier config)
- Use semicolons
- Use single quotes for strings
- Use trailing commas in objects/arrays
- Maximum line length: Follow Prettier defaults (except CSS: 500 chars)

**Naming Conventions:**
- Components: PascalCase (`OrderBumpProduct`)
- Functions: camelCase (`handleSave`, `getFlowData`)
- Constants: UPPER_SNAKE_CASE (`API_BASE_URL`)
- Files: Match component/function name

### 3.3 CSS/SCSS Rules

#### 3.3.1 Stylelint Configuration

- Follow `@wordpress/stylelint-config`
- Max line length: 500 characters
- Use single quotes for URLs and font families
- Function parentheses must have space inside: `calc( 10px + 5px )`

#### 3.3.2 CSS Naming Conventions

**BEM-like Naming (for legacy CSS):**
```css
.wcf-component {}
.wcf-component__element {}
.wcf-component--modifier {}
```

**Class Prefix:**
- All classes must be prefixed with `wcf-` or `cartflows-`
- Admin classes: `wcf-admin-*`
- Frontend classes: `wcf-*` or `cartflows-*`

**Tailwind CSS (Admin UI):**
- Use Tailwind utility classes for admin interface
- Custom components can use Tailwind with `@apply`
- Maintain consistency with WordPress admin styles

#### 3.3.3 CSS Organization

```scss
// 1. Variables
$primary-color: #0073aa;

// 2. Mixins
@mixin button-style {
    // styles
}

// 3. Base styles
.wcf-component {
    // base styles
}

// 4. Modifiers
.wcf-component--modifier {
    // modifier styles
}

// 5. Responsive
@media (max-width: 768px) {
    // mobile styles
}
```

#### 3.3.4 RTL Support

- Use `rtlcss` for RTL styles (generated automatically via Grunt)
- Test layouts in RTL mode
- Use logical properties where possible (`margin-inline-start` instead of `margin-left`)

---

## 4. CODING STANDARDS AND LINTING EXPECTATIONS

### 4.1 PHP Linting (PHPCS)

**Command:** `composer lint` or `vendor/bin/phpcs --standard=phpcs.xml.dist`

**Standards Applied:**
- WordPress-Core
- WordPress-Docs
- WordPress-Extra
- WordPress-VIP-Go
- PHPCompatibility (PHP 5.6+)

**Excluded Paths:**
- `admin-core/assets/*`
- `assets/*`
- `dist/*`, `build/*`
- `node_modules/*`
- `vendor/*`
- `libraries/*`
- Generated files

**Auto-fix:** `composer format` (uses phpcbf)

### 4.2 PHP Static Analysis (PHPStan)

**Command:** `composer phpstan`

**Configuration:**
- Level: 9 (maximum strictness)
- Includes WordPress stubs
- Includes WooCommerce stubs
- Baseline file: `phpstan-baseline.neon` (for existing issues)

**Requirements:**
- All new code must pass PHPStan level 9
- No new errors should be added to baseline
- Use type hints and PHPDoc annotations

### 4.3 JavaScript Linting (ESLint)

**Command:** `npm run lint-js`

**Configuration:**
- `@wordpress/eslint-plugin/recommended-with-formatting`
- Text domain validation: `'cartflows'`
- Custom globals defined in `.eslintrc.js`

**Auto-fix:** `npm run lint-js:fix`

### 4.4 CSS Linting (Stylelint)

**Command:** `npm run lint-css`

**Configuration:**
- `@wordpress/stylelint-config`
- Custom rules in `.stylelintrc`

**Auto-fix:** `npm run lint-css:fix`

### 4.5 Code Formatting (Prettier)

**Command:** `npm run pretty:fix`

**Configuration:**
- WordPress Prettier config (`@wordpress/prettier-config`)
- Custom overrides for CSS (500 char line length)
- Custom overrides for YAML (2 space indent)

### 4.6 Pre-commit Hooks

- Git pre-commit hook runs linting checks
- Fix issues before committing
- Use `npm run lint-js:fix` and `composer format` before committing

### 4.7 Linting Enforcement Rules for AI

**All generated or modified PHP code must comply with:**
- **PHPCS** (WordPress Core, Extra, Docs, VIP-Go standards)
- **PHPStan level 9** (maximum static analysis strictness)
- **PHPInsights** (no architectural or complexity changes unless explicitly requested)

**AI Code Quality Requirements:**
- AI must **not introduce new linting, static analysis, or insight violations**
- AI must **not auto-fix unrelated linting issues** unless explicitly requested
- Focus only on the specific task while maintaining existing code quality standards

---

## 5. NAMING CONVENTIONS

### 5.1 PHP Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | `Cartflows_{Feature}` | `Cartflows_Loader`, `Cartflows_Helper` |
| Methods | `snake_case` | `get_instance()`, `save_step_meta()` |
| Variables | `snake_case` | `$step_id`, `$flow_data` |
| Constants | `UPPER_SNAKE_CASE` | `CARTFLOWS_VERSION`, `CARTFLOWS_FILE` |
| Hooks | `cartflows_{context}_{action}` | `cartflows_order_started`, `cartflows_checkout_data` |
| Functions | `cartflows_{feature}_{action}` | `cartflows_get_flow()`, `cartflows_is_checkout_page()` |
| Database Tables | `{prefix}cartflows_{name}` | `wp_cartflows_flows`, `wp_cartflows_flow_meta` |
| Options | `cartflows_{feature}_{setting}` | `cartflows_global_settings`, `cartflows_checkout_fields` |

### 5.2 JavaScript/React Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Components | `PascalCase` | `OrderBumpProduct`, `FlowEditor` |
| Functions | `camelCase` | `handleSave()`, `getFlowData()` |
| Variables | `camelCase` | `stepId`, `flowData` |
| Constants | `UPPER_SNAKE_CASE` | `API_BASE_URL`, `DEFAULT_SETTINGS` |
| Files | Match component/function | `OrderBumpProduct.js`, `apiHelpers.js` |
| CSS Classes | `wcf-{component}-{element}` | `wcf-order-bump`, `wcf-order-bump__title` |

### 5.3 CSS/SCSS Naming

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | `wcf-{component}[-{modifier}]` | `wcf-checkout`, `wcf-checkout--two-step` |
| IDs | `wcf-{component}-{element}` | `wcf-checkout-form` |
| Variables | `$wcf-{name}` | `$wcf-primary-color`, `$wcf-spacing` |
| Mixins | `wcf-{name}` | `wcf-button-style`, `wcf-responsive` |

### 5.4 Database Naming

- Tables: `{prefix}cartflows_{name}` (e.g., `wp_cartflows_flows`)
- Meta keys: `cartflows_{feature}_{setting}` (e.g., `cartflows_checkout_fields`)
- Transients: `cartflows_{context}_{data}` (e.g., `cartflows_flow_data_123`)

---

## 6. HOW AI SHOULD BEHAVE WHEN GENERATING, EDITING, OR REFACTORING CODE

### 6.1 Code Generation Rules

1. **Always Follow Existing Patterns:**
   - Study similar files before generating new code
   - Match the coding style and structure of existing code
   - Use the same patterns for hooks, filters, and actions

2. **Complete Implementations:**
   - Generate complete, working code (not placeholders)
   - Include all necessary imports/dependencies
   - Add proper error handling
   - Include security checks (nonces, capabilities, sanitization)

3. **Documentation:**
   - Add PHPDoc comments for all classes, methods, and properties
   - Add JSDoc comments for JavaScript functions
   - Include `@since` tags for version tracking
   - Document parameters and return values

4. **Testing Considerations:**
   - Write testable code (avoid hard dependencies)
   - Use dependency injection where appropriate
   - Make functions pure when possible

### 6.2 Code Editing Rules

1. **Preserve Existing Style:**
   - Maintain existing indentation (tabs for PHP, spaces for JS)
   - Keep existing naming conventions
   - Don't reformat entire files unless explicitly requested

2. **Incremental Changes:**
   - Make minimal changes to achieve the goal
   - Don't refactor unrelated code
   - Preserve existing functionality

3. **Backward Compatibility:**
   - Maintain backward compatibility when possible
   - Use deprecation notices for breaking changes
   - Follow WordPress versioning practices

4. **Error Handling:**
   - Don't remove existing error handling
   - Improve error messages if needed
   - Add error handling for new code paths

### 6.3 Refactoring Rules

1. **Analysis First:**
   - Understand the current implementation
   - Identify dependencies and side effects
   - Check for existing tests

2. **Incremental Refactoring:**
   - Break large refactors into smaller steps
   - Test after each step
   - Maintain functionality throughout

3. **Pattern Consistency:**
   - Use existing patterns (singleton, factory, etc.)
   - Follow the same architecture as similar modules
   - Maintain the same file structure

4. **Documentation Updates:**
   - Update PHPDoc/JSDoc comments
   - Update inline comments if logic changes
   - Update README if architecture changes

### 6.4 Code Review Checklist

Before submitting code, ensure:

- [ ] PHPCS passes (`composer lint`)
- [ ] PHPStan passes (`composer phpstan`)
- [ ] ESLint passes (`npm run lint-js`)
- [ ] Stylelint passes (`npm run lint-css`)
- [ ] Prettier formatting applied (`npm run pretty:fix`)
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Nonces verified for AJAX/form submissions
- [ ] Capabilities checked for admin operations
- [ ] Database queries use `$wpdb->prepare()`
- [ ] Internationalization functions used (`__()`, `esc_html__()`, etc.)
- [ ] PHPDoc/JSDoc comments added
- [ ] No console.log or debug code left
- [ ] No hardcoded values (use constants or options)
- [ ] Error handling implemented
- [ ] Backward compatibility maintained (if applicable)

---

## 7. DOCUMENTATION AND COMMENT RULES

### 7.1 PHPDoc Standards

**Class Documentation:**
```php
/**
 * Class Description.
 *
 * @package CartFlows
 * @since 1.0.0
 */
class Cartflows_Example {
```

**Method Documentation:**
```php
/**
 * Method description explaining what it does.
 *
 * @param string $param1 Parameter description.
 * @param int    $param2 Optional parameter. Default 0.
 * @since 1.0.0
 * @return mixed Return description.
 */
public function example_method( $param1, $param2 = 0 ) {
```

**Property Documentation:**
```php
/**
 * Member Variable
 *
 * @var string Variable description.
 * @since 1.0.0
 */
private $property;
```

**Hook Documentation:**
```php
/**
 * Fires before checkout is rendered.
 *
 * @param int $step_id Step ID.
 * @param int $flow_id Flow ID.
 * @since 1.0.0
 */
do_action( 'cartflows_before_checkout_render', $step_id, $flow_id );
```

### 7.2 JSDoc Standards

**Component Documentation:**
```javascript
/**
 * Component description.
 *
 * @param {Object} props Component props.
 * @param {string} props.title Component title.
 * @param {Function} props.onChange Change handler.
 * @return {JSX.Element} Component element.
 */
const ExampleComponent = ( { title, onChange } ) => {
```

**Function Documentation:**
```javascript
/**
 * Function description.
 *
 * @param {string} flowId Flow ID.
 * @param {Object} data Flow data.
 * @return {Promise<Object>} Promise resolving to flow data.
 */
const saveFlow = async ( flowId, data ) => {
```

### 7.3 Inline Comments

**When to Comment:**
- Complex logic or algorithms
- Workarounds for bugs or limitations
- Business logic decisions
- Non-obvious code behavior

**Comment Style:**
```php
// ✅ GOOD - Explains why, not what
// Check if user has access before processing to prevent unauthorized access.
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

// ❌ BAD - States the obvious
// Check if user can manage options
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
```

**TODO Comments:**
```php
// @todo: Refactor this method to use dependency injection (v2.2.0)
// @todo: Add unit tests for this function
// @todo: Optimize database query (performance issue)
```

### 7.4 README and Documentation Files

- Keep `readme.txt` updated for WordPress.org
- Update `changelog.txt` for each release
- Document new features in code comments
- Add usage examples for complex functions

---

## 8. SECURITY AND VALIDATION RULES (ESPECIALLY FOR WORDPRESS)

### 8.1 Input Validation

**Always Validate:**
- User input from `$_GET`, `$_POST`, `$_REQUEST`
- AJAX request data
- REST API request parameters
- File uploads
- Database query parameters

**Validation Functions:**
```php
// Integers
$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

// Strings
$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

// Emails
$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

// URLs
$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

// Arrays
$items = isset( $_POST['items'] ) && is_array( $_POST['items'] ) 
    ? array_map( 'sanitize_text_field', wp_unslash( $_POST['items'] ) ) 
    : array();

// JSON
$data = isset( $_POST['data'] ) ? json_decode( wp_unslash( $_POST['data'] ), true ) : array();
if ( json_last_error() !== JSON_ERROR_NONE ) {
    // Handle error
}
```

### 8.2 Output Escaping

**Always Escape:**
- Data displayed in HTML
- Data in HTML attributes
- Data in JavaScript
- Data in URLs
- Data in CSS

**Escaping Functions:**
```php
// HTML content
echo esc_html( $text );
echo wp_kses_post( $html ); // For trusted HTML

// HTML attributes
echo '<div class="' . esc_attr( $class ) . '">';

// URLs
echo '<a href="' . esc_url( $url ) . '">';

// JavaScript (use wp_localize_script for complex data)
echo '<script>var data = ' . wp_json_encode( $data ) . ';</script>';

// CSS
echo 'style="color: ' . esc_attr( $color ) . ';"';
```

### 8.3 Nonce Verification

**For Forms:**
```php
// Generate nonce
wp_nonce_field( 'cartflows_save_flow', 'cartflows_nonce' );

// Verify nonce
if ( ! isset( $_POST['cartflows_nonce'] ) 
    || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cartflows_nonce'] ) ), 'cartflows_save_flow' ) 
) {
    wp_die( esc_html__( 'Security check failed.', 'cartflows' ) );
}
```

**For AJAX:**
```php
// Generate nonce
wp_localize_script( 'script-handle', 'cartflowsData', array(
    'nonce' => wp_create_nonce( 'cartflows_ajax' ),
) );

// Verify nonce
check_ajax_referer( 'cartflows_ajax', 'nonce' );
```

### 8.4 Capability Checks

**Always Check:**
- Admin operations: `current_user_can( 'manage_options' )`
- Edit posts: `current_user_can( 'edit_post', $post_id )`
- Custom capabilities: `current_user_can( 'cartflows_edit_flows' )`

```php
// ✅ CORRECT
if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( array( 
        'message' => __( 'You do not have permission to perform this action.', 'cartflows' ) 
    ) );
}
```

### 8.5 Database Security

**Always Use Prepared Statements:**
```php
global $wpdb;

// ✅ CORRECT
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}cartflows_flows WHERE id = %d AND status = %s",
        $flow_id,
        'publish'
    )
);

// ❌ WRONG
$results = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}cartflows_flows WHERE id = $flow_id"
);
```

**Table Names:**
- Always use `$wpdb->prefix` for table names
- Never allow user input in table names

### 8.6 File Security

**File Operations:**
```php
// ✅ CORRECT - Validate file paths
$file_path = realpath( $base_path . $relative_path );
if ( $file_path && strpos( $file_path, realpath( $base_path ) ) === 0 ) {
    // Safe to use
}

// File uploads
$uploaded_file = $_FILES['file'];
$file_type = wp_check_filetype( $uploaded_file['name'] );
$allowed_types = array( 'jpg', 'png', 'gif' );

if ( ! in_array( $file_type['ext'], $allowed_types, true ) ) {
    wp_send_json_error( array( 'message' => __( 'Invalid file type.', 'cartflows' ) ) );
}
```

### 8.7 XSS Prevention

- Never trust user input
- Always escape output
- Use `wp_kses()` for HTML content
- Use `wp_kses_post()` for post content
- Use `esc_html()`, `esc_attr()`, `esc_url()` appropriately

### 8.8 CSRF Prevention

- Always use nonces for forms and AJAX
- Verify nonces before processing requests
- Use `check_ajax_referer()` for AJAX requests

### 8.9 SQL Injection Prevention

- Always use `$wpdb->prepare()`
- Never concatenate user input into SQL queries
- Use `$wpdb` methods instead of raw SQL when possible

---

## 9. TESTING GUIDELINES

### 9.1 PHP Unit Tests

**Location:** `tests/php/`

**Framework:** PHPUnit 8+

**Test Structure:**
```php
<?php
/**
 * Test class description.
 *
 * @package CartFlows
 * @group   cartflows
 */
class Test_Cartflows_Example extends WP_UnitTestCase {

	/**
	 * Test method description.
	 */
	public function test_example_method() {
		// Arrange
		$expected = 'expected_value';
		
		// Act
		$actual = cartflows_example_function();
		
		// Assert
		$this->assertEquals( $expected, $actual );
	}
}
```

**Running Tests:**
```bash
composer test
# or
vendor/bin/phpunit
```

### 9.2 E2E Tests

**Location:** `tests/e2e/`

**Framework:** Jest with Puppeteer (@wordpress/e2e-test-utils)

**Running Tests:**
```bash
npm run test:e2e
npm run test:e2e:interactive
```

### 9.3 Playwright Tests

**Location:** `tests/play/`

**Framework:** Playwright

**Running Tests:**
```bash
npm run play:run
npm run play:run:interactive
```

### 9.4 Test Coverage

- Aim for high test coverage for critical functionality
- Test edge cases and error conditions
- Test security measures (nonces, capabilities, sanitization)
- Test backward compatibility

### 9.5 Manual Testing Checklist

Before submitting code:
- [ ] Test in WordPress 5.8+
- [ ] Test with WooCommerce 3.0+
- [ ] Test with different themes
- [ ] Test with different page builders
- [ ] Test in RTL mode
- [ ] Test on mobile devices
- [ ] Test with different user roles
- [ ] Test error conditions
- [ ] Test with debug mode enabled

---

## 10. OUTPUT FORMAT EXPECTATIONS

### 10.1 Complete Code

**When generating new code:**
- Provide complete, working implementations
- Include all necessary imports/dependencies
- Include error handling
- Include security checks
- Include documentation

**Example:**
```php
<?php
/**
 * New feature class.
 *
 * @package CartFlows
 * @since 2.1.18
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cartflows_New_Feature.
 */
class Cartflows_New_Feature {

	/**
	 * Instance
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @since 2.1.18
	 * @return object
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 2.1.18
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize
	 *
	 * @since 2.1.18
	 * @return void
	 */
	public function init() {
		// Implementation
	}
}
```

### 10.2 Diffs

**When editing existing code:**
- Show clear diffs with context
- Explain what changed and why
- Highlight security implications
- Note backward compatibility concerns

**Format:**
```diff
--- a/file.php
+++ b/file.php
@@ -10,6 +10,7 @@
 	public function example_method( $param ) {
+		// Added security check
 		if ( ! current_user_can( 'manage_options' ) ) {
 			return;
 		}
```

### 10.3 Comments

**When explaining code:**
- Explain the "why" not just the "what"
- Reference related code/files
- Note potential issues or improvements
- Provide context for complex logic

**Example:**
```php
// Check user capability before processing to prevent unauthorized access.
// This is critical for security as this method modifies flow data.
if ( ! current_user_can( 'manage_options' ) ) {
    wp_send_json_error( array( 
        'message' => __( 'Permission denied.', 'cartflows' ) 
    ) );
}
```

### 10.4 Code Blocks

**Always include:**
- Language identifier (```php, ```javascript, etc.)
- Complete, runnable code
- Proper indentation
- All necessary context

### 10.5 File Paths

**Always use:**
- Absolute paths when possible
- Relative paths from project root when appropriate
- Clear file references

**Example:**
```
File: /path/to/cartflows/classes/class-cartflows-example.php
```

---

## 11. ADDITIONAL GUIDELINES

### 11.1 Internationalization (i18n)

**PHP:**
```php
// ✅ CORRECT
__( 'Text to translate', 'cartflows' );
esc_html__( 'Text to translate', 'cartflows' );
sprintf( __( 'Hello %s', 'cartflows' ), $name );
_n( 'One item', '%d items', $count, 'cartflows' );

// ❌ WRONG
'Text to translate'
"Hello $name"
```

**JavaScript:**
```javascript
// ✅ CORRECT
import { __, sprintf, _n } from '@wordpress/i18n';

__( 'Text to translate', 'cartflows' );
sprintf( __( 'Hello %s', 'cartflows' ), name );
_n( 'One item', '%d items', count, 'cartflows' );
```

**Text Domain:** Always use `'cartflows'` (lowercase, no spaces)

### 11.2 Version Management

- Use semantic versioning (MAJOR.MINOR.PATCH)
- Update version in `cartflows.php`
- Update version in `package.json`
- Add changelog entry in `changelog.txt`
- Tag releases in git

### 11.3 Performance Considerations

- Minimize database queries (use transients for expensive operations)
- Cache frequently accessed data
- Lazy load assets when possible
- Optimize images and assets
- Use WordPress object cache when appropriate

### 11.4 Accessibility (a11y)

- Use semantic HTML
- Include ARIA labels where needed
- Ensure keyboard navigation works
- Test with screen readers
- Maintain color contrast ratios
- Provide alternative text for images

### 11.5 Browser Compatibility

- Support modern browsers (last 2 versions)
- Test in Chrome, Firefox, Safari, Edge
- Test on mobile devices
- Use feature detection, not browser detection

---

## 12. COMMON PATTERNS AND EXAMPLES

### 12.1 Singleton Pattern

```php
class Cartflows_Example {
	private static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Constructor
	}
}
```

### 12.2 AJAX Handler

```php
class Cartflows_Ajax_Example extends Cartflows_Ajax_Base {

	public function register_ajax_events() {
		$ajax_events = array(
			'save_example' => array(
				'callback' => 'save_example',
				'permission' => 'manage_options',
			),
		);
		return $ajax_events;
	}

	public function save_example() {
		check_ajax_referer( 'cartflows_ajax', 'nonce' );

		$data = isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '';

		// Process data
		$result = $this->process_data( $data );

		wp_send_json_success( $result );
	}
}
```

### 12.3 REST API Endpoint

```php
class Cartflows_API_Example extends Cartflows_API_Base {

	public function register_routes() {
		register_rest_route(
			$this->get_namespace(),
			'/example',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_example' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	public function get_example( $request ) {
		$data = array( 'example' => 'data' );
		return new WP_REST_Response( $data, 200 );
	}
}
```

### 12.4 React Component with API Call

```javascript
import React, { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const ExampleComponent = () => {
	const [ data, setData ] = useState( null );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		const fetchData = async () => {
			try {
				const response = await apiFetch( {
					path: '/cartflows/v1/example',
				} );
				setData( response );
			} catch ( error ) {
				console.error( error );
			} finally {
				setLoading( false );
			}
		};

		fetchData();
	}, [] );

	if ( loading ) {
		return <div>{ __( 'Loading...', 'cartflows' ) }</div>;
	}

	return <div>{ data && JSON.stringify( data ) }</div>;
};

export default ExampleComponent;
```

---

## 13. TROUBLESHOOTING AND DEBUGGING

### 13.1 Debugging Tools

**PHP:**
- `wcf()->logger->log()` - CartFlows logger
- `WP_DEBUG` - WordPress debug mode
- `error_log()` - PHP error log (development only)

**JavaScript:**
- Browser DevTools console
- React DevTools
- Redux DevTools

### 13.2 Common Issues

**PHP:**
- Missing ABSPATH check
- Missing nonce verification
- Incorrect hook priority
- Database query without prepare()

**JavaScript:**
- Missing text domain in i18n functions
- Incorrect import paths
- Missing error handling in async functions
- State update after unmount

### 13.3 Getting Help

- Check existing code for similar patterns
- Review WordPress Codex for functions
- Check WooCommerce documentation for hooks
- Review CartFlows codebase for examples

---

## CONCLUSION

This guide serves as the definitive reference for AI agents working on the CartFlows codebase. When in doubt:

1. **Follow existing patterns** - Look at similar code in the codebase
2. **Prioritize security** - Always validate input, escape output, verify nonces
3. **Maintain consistency** - Follow naming conventions and code style
4. **Document thoroughly** - Add PHPDoc/JSDoc comments
5. **Test thoroughly** - Ensure code works and passes linting

**Remember:** Code quality, security, and maintainability are paramount. When generating or editing code, always prioritize these principles.

---

**Last Updated:** 2025  
**Maintained By:** CartFlows Development Team  
**For Questions:** Refer to this document or existing codebase patterns

