# WordPress Practice Tests Plugin

## Overview

This WordPress plugin provides a system for creating, managing, and delivering practice tests, specifically designed for ACT and SAT preparation, within a WordPress website. It allows administrators to manage a question bank, define tests, and manage user accounts (differentiating between "Guardian" and "Student" roles). Registered users can log in, take tests, view their results (including basic performance breakdowns), and guardians can link to and view their students' performance.

## Core Purpose

The primary goal of this plugin is to establish an online platform where students can take practice ACT and SAT tests, receive feedback on their performance by category, and allow guardians to monitor progress. It aims to provide tools for administrators to easily manage test content and user access.

## Key Functionalities (Current State)

### 1. Database Structure (`db-functions.php`)

*   **Relational Schema:** Uses custom tables with a relational structure:
    *   `wp_practice_test_questions`: Stores individual questions (type, passage, text, choices, correct answer, explanation, subject, skill).
    *   `wp_practice_test_subs`: Stores user data (name, email, hashed password, role, status, registration date, plan, aggregate performance stats).
    *   `wp_practice_test_tests`: Stores test metadata (name, description, type).
    *   `wp_practice_test_test_questions`: Links tests to specific questions with ordering.
    *   `wp_practice_test_test_attempts`: Tracks individual user attempts at tests (status, start/end times, score).
    *   `wp_practice_test_test_resp`: Stores user answers for each question within an attempt (answer given, correctness).
    *   `wp_practice_test_guardian_student_links`: Links guardian users to student users.
*   **Activation:** Creates/updates these tables using `dbDelta` on plugin activation.
*   **Uninstallation:** Drops all custom tables on plugin uninstall (`uninstall.php`).
*   **Data Handling:** Includes functions for adding questions/users (single & CSV), password hashing, custom login verification.

### 2. Admin Interface (`admin-menu.php`, `includes/admin/`)

*   **WP Admin Menu:** Adds a "Practice Tests" menu.
*   **Sub-Pages:**
    *   **Dashboard:** Placeholder.
    *   **Add Question:** Form for single question entry and CSV upload.
    *   **View/Edit Question:** Displays questions using `WP_List_Table` with sorting, searching, pagination, and bulk delete actions. (Edit action link present but not implemented).
    *   **Define Test:** UI to create new tests by selecting questions from the bank.
    *   **Manage Users:** Displays users using `WP_List_Table` with sorting, searching, pagination, and bulk delete actions. Includes forms for adding single users or via CSV. (Edit action link present but not implemented).

### 3. Frontend User Experience (`core-functions.php`, `client-*.php`, `js/`)

*   **Shortcodes:**
    *   `[practice_test_quiz test_id="X"]`: Displays a specific test (fetched by ID), paginated one question per page. Handles test attempts (start/resume). Uses AJAX (`js/quiz-handler.js`) to save answers on change, pause (save & exit), and submit the test.
    *   `[practice_test_reg_guardian]`: Registration form for Guardian users.
    *   `[practice_test_reg_student]`: Registration form for Student users.
    *   `[practice_tests_login]`: Login form.
    *   `[practice_tests_members]`: Renders the dynamic members' area dashboard. Uses AJAX (`js/members-handler.js`) to load content sections (Available Tests, Performance Breakdown, Results History, Add Student) without page reloads.
    *   `[practice_tests_plan]`: Placeholder.
    *   `[practice_test_results]`: Displays results for a completed attempt (specified by URL param `?attempt=X`). Shows overall score and question-by-question review including subject/skill, user answer, correct answer, and explanation.
    *   `[practice_test_performance_breakdown]`: Displays the logged-in user's overall performance accuracy broken down by subject and skill across all completed tests.
*   **User Roles:** Differentiates between 'guardian' and 'student'.
*   **Guardian Features:** Can link student accounts via email and view linked students' performance breakdowns within the members' area.
*   **Client-Side Validation:** `email-validation.js` uses AJAX for real-time email existence check during registration.
*   **Login Redirect:** `redirect.js` redirects from `/login-success` to `/members` after a delay.

### 4. Plugin Lifecycle & Loading (`practice-tests.php`)

*   **Loading:** Uses `plugins_loaded` hook to initialize most components, ensuring dependencies are met. Admin-specific code is loaded conditionally using `is_admin()`.
*   **Activation/Deactivation:** Hooks registered correctly.
*   **AJAX:** Multiple AJAX actions defined for frontend interactions (quiz handling, members area loading, email check, student linking).

## Recent Development Summary (Phase 1 & 4 + Debugging)

*   **Core Platform (Phase 1):** Refactored database to relational model, added Test Definition UI, implemented test taking flow with AJAX, added basic grading and results display, fixed CSV upload and uninstall bugs.
*   **UX/UI & Features (Phase 4):** Implemented detailed performance breakdown (by subject/skill), made members area dynamic with AJAX loading, improved admin tables using `WP_List_Table`, enhanced results page with subject/skill info, implemented guardian-student linking and performance viewing.
*   **Debugging:** Resolved activation fatal errors related to file includes and parse errors.

---

## Evaluation for Monetization & AI Goal (Ruthless)

The current plugin provides a **functional foundation** but still requires significant work to meet the commercial goals:

*   **AI Integration (USP):** **Non-Existent.** Core value proposition is missing.
*   **Monetization Readiness:** **Very Low.** Lacks plan definition, access control, and payment gateway integration.
*   **Core Functionality:** **Improved but Basic.** Test taking works, but lacks features like timers, advanced review options. Performance stats are basic (accuracy only).
*   **User Experience (UX/UI):** **Functional but Unpolished.** AJAX loading helps, but styling is minimal. Admin tables are standard but lack inline editing.
*   **Code Quality & Maintainability:** **Improved.** Refactoring helped, but needs further cleanup, error handling, and potentially object-oriented structure for core logic.
*   **Performance Tracking by Category:** **Basic Implementation.** Accuracy is calculated and displayed, but lacks depth (e.g., timing, historical trends, identifying specific weak questions within a skill). User aggregate stats (`best_subject` etc.) are not yet calculated.
*   **Security:** Requires a dedicated audit before commercial release.

**Conclusion:** The plugin is now a much better base, but significant development remains for AI features, monetization, advanced analytics, UI polish, and hardening.

---

## TODO List Towards Monetizable AI Platform

**[✓] Phase 1: Core Test Platform Functionality (Complete)**
*Items 1-6 implemented.*

**(Phase 2: AI Integration - The USP)**

7.  **Develop/Integrate AI Question Generation:** Build or integrate AI service/API. Create admin UI for triggering/reviewing AI-generated questions.

**(Phase 3: Monetization)**

8.  **Implement Plans & Access Control:** Admin UI for plan definition (Free/Premium, limits). Enforce access control based on user plan. Implement upgrade flow. Make `[practice_tests_plan]` functional.
9.  **Integrate Payment Gateway:** Integrate with Stripe/PayPal etc. for subscription handling.

**[→] Phase 4: UX/UI & Advanced Features (Partially Complete)**

10. **[✓] Implement Detailed Performance Display:** Created views/shortcode for user's own breakdown by subject/skill; integrated into members area via AJAX.
11. **[✓] Enhance Members Area:** Implemented dynamic content loading via AJAX. (Styling pending).
12. **[✓] Improve Admin Interface:** Implemented `WP_List_Table` for questions/users. (Inline editing pending).
13. **[✓] Develop Advanced Feedback:** Added Subject/Skill to results page. (Linking resources pending).
14. **[✓] Guardian/Student Linking:** Implemented linking and viewing student performance. (Unlinking, deeper integration pending).

**(Phase 5: Quality & Polish)**

15. **Refactor Codebase:** Improve structure, comments, standards adherence, error handling, add tests.
16. **Security Audit & Hardening:** Thorough security review and implementation.
17. **Performance Optimization:** Analyze and optimize DB queries and code, especially for performance analysis calculations.
18. **Documentation:** Comprehensive admin and user docs.
