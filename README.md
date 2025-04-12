# WordPress Practice Tests Plugin

## Overview

This WordPress plugin provides a system for creating, managing, and delivering practice tests, specifically designed for ACT and SAT preparation, within a WordPress website. It allows administrators to manage a question bank and user accounts (differentiating between "Guardian" and "Student" roles), while registered users can log in to take tests and potentially view results (though result display is not fully implemented).

## Core Purpose

The primary goal of this plugin is to establish an online platform where students can take practice ACT and SAT tests. It aims to provide tools for administrators to easily manage test content and user access, and for users to register, log in, and interact with the tests.

## Key Functionalities

### 1. Database Structure & Management (`db-functions.php`)

*   **Custom Tables:** Creates four essential tables upon activation:
    *   `wp_practice_test_questions`: Stores individual questions (type, passage, text, choices, correct answer, explanation, subject, skill).
    *   `wp_practice_test_subs`: Stores user data (name, email, hashed password, role, status, registration date, plan, basic performance stats like `tests_taken`, `questions_answered`, etc.).
    *   `wp_practice_test_tests`: Designed to define specific tests by linking up to 50 question IDs.
    *   `wp_practice_test_test_resp`: Intended to store individual user responses for each question within a test.
*   **Data Handling:** Includes functions for adding questions/users individually or via CSV upload (Note: User CSV upload function seems to target the wrong table). Handles password hashing (`wp_hash_password`) and login verification (`wp_check_password`).
*   **CRUD Operations:** Provides basic functions for creating, reading, updating, and deleting questions, tests, and users (subscribers).

### 2. Admin Interface (`admin-menu.php`, `utilities.php`)

*   **WP Admin Menu:** Adds a "Practice Tests" top-level menu.
*   **Sub-Pages:**
    *   **Dashboard:** Placeholder for future analytics.
    *   **Add Question:** Form for single question entry and CSV upload.
    *   **View/Edit Question:** Displays the question bank using `render_question_bank_table()` (from `utilities.php`) with filtering and pagination. Editing is intended but not fully implemented.
    *   **Manage Users:** Form for single user management (add/update/reset/delete) and CSV upload. Displays user list using `render_users_table()` (from `utilities.php`) with filtering and pagination, showing user details and performance stats.

### 3. Frontend User Experience (`core-functions.php`, `client-*.php`, JS files)

*   **Shortcodes:** The primary way to display plugin content on the site:
    *   `[practice_test_quiz testtype="ACT/SAT"]`: Displays a paginated quiz with questions and radio button answers. Includes navigation/action buttons.
    *   `[practice_test_reg_guardian]`: Registration form for Guardian users.
    *   `[practice_test_reg_student]`: Registration form for Student users.
    *   `[practice_tests_login]`: Login form.
    *   `[practice_tests_members]`: Renders the main dashboard for logged-in users.
    *   `[practice_tests_plan]`: Placeholder for displaying user subscription plans.
*   **User Roles:** Differentiates between 'guardian' and 'student' roles during registration and potentially in the members' area ("Add Student" link visible).
*   **Members Area:** Provides a dashboard structure with sidebar and top navigation (Take Test, See Results, Upgrade, Add Student, Profile, Sign Out). Content areas are mostly placeholders, suggesting dynamic loading via JavaScript is intended.
*   **Client-Side Validation:** `email-validation.js` uses AJAX to check if an email exists during registration, providing immediate feedback and disabling submit if necessary.
*   **Login Redirect:** `redirect.js` automatically redirects users from a `/login-success` page to the `/members` area after a 3-second delay.

### 4. Plugin Lifecycle (`practice-tests.php`, `uninstall.php`)

*   **Activation:** Creates the necessary database tables.
*   **Deactivation:** Currently does not perform specific cleanup (table dropping is commented out in `db-functions.php`).
*   **Uninstallation:** Attempts to drop the `questions` and `subs` tables (contains a potential typo `$$sql_subs` and omits dropping the `tests` and `test_resp` tables).

## Key User Flows

1.  **Admin - Question Management:** Admin navigates to "Practice Tests" -> "Add Question" to add questions individually or upload a CSV. They can view questions via "View/Edit Question".
2.  **Admin - User Management:** Admin navigates to "Practice Tests" -> "Manage Users" to add/manage users individually or upload a CSV. They can view the user list and basic stats.
3.  **User - Registration:** A visitor navigates to a page with `[practice_test_reg_guardian]` or `[practice_test_reg_student]`, fills the form. Email is validated via AJAX. On submission, user data is saved to `wp_practice_test_subs`.
4.  **User - Login:** A user navigates to a page with `[practice_tests_login]`, enters credentials. `practice_tests_custom_login` verifies against the database. On success, redirects to `/login-success`, then automatically to `/members`.
5.  **User - Taking a Test (Inferred):** A logged-in user in the `/members` area likely clicks "Take Test" or selects a test from the display area. This loads a page/view containing the `[practice_test_quiz]` shortcode. The user answers questions page by page, potentially using "Save and Exit" or "Save and Submit". (The exact mechanism for saving/submitting is not detailed in the PHP).
6.  **User - Viewing Results (Inferred):** A user might click "See Results" in the members' area, which would query the `wp_practice_test_test_resp` table (logic not shown) to display performance.

## Potential Issues & Areas for Development

*   **User CSV Upload:** The `practice_tests_handle_users_csv_upload` function in `db-functions.php` incorrectly targets the questions table.
*   **Uninstall Script:** Contains a typo (`$$sql_subs`) and doesn't remove all created tables (`tests`, `test_resp`).
*   **Incomplete Features:** User plans (`[practice_tests_plan]`), detailed analytics, test result processing/display, admin editing of questions, and dynamic content loading in the members' area appear incomplete or are placeholders.
*   **Test Definition:** While a `wp_practice_test_tests` table exists, the admin interface lacks a dedicated section to create/manage these defined tests (linking questions together).
*   **Security:** While basic sanitization and password hashing are used, a full security audit would be recommended, especially regarding user input handling and capabilities checks (`manage_options` is used, which is appropriate for admin functions).
*   **Error Handling:** Robust error handling for database operations and file uploads could be improved.

This README provides a snapshot of the plugin's structure and capabilities based on the provided source code.

---

## Recent Development (Phase 1 Implementation)

Significant progress has been made on the core test platform functionality outlined in Phase 1 of the TODO list:

*   **Database Refactoring:** The database schema in `db-functions.php` was updated to use a relational structure. The inflexible fixed-column tables for tests and responses were replaced with:
    *   `wp_practice_test_tests`: Stores test metadata.
    *   `wp_practice_test_test_questions`: Links tests to questions with ordering.
    *   `wp_practice_test_test_attempts`: Tracks individual user attempts.
    *   `wp_practice_test_test_resp`: Stores user answers per question within an attempt.
*   **Test Definition UI:** A new "Define Test" admin page was added (`admin-menu.php`), allowing administrators to create tests by selecting questions from the bank.
*   **Test Taking Flow:**
    *   The `[practice_test_quiz]` shortcode (`core-functions.php`) was rewritten to load questions based on a specified `test_id`, create/resume test attempts, handle pagination for the specific test, and load saved answers.
    *   AJAX handlers were added (`core-functions.php`) to save answers (`save_practice_test_answer`), pause tests (`pause_practice_test`), and submit tests (`submit_practice_test`).
    *   A new `js/quiz-handler.js` file was created to manage frontend AJAX interactions during the quiz.
*   **Grading:** A basic grading function (`practice_test_grade_attempt` in `core-functions.php`) was implemented. It's triggered on submission, calculates the overall score, marks answers in the database, and updates basic user stats (`tests_taken`, `questions_answered`).
*   **Results Display:** A new `[practice_test_results]` shortcode (`core-functions.php`) was added to display basic results (overall score, question breakdown with user/correct answers, explanations) for a completed attempt based on a URL parameter.
*   **Bug Fixes:**
    *   The user CSV upload function (`practice_tests_handle_users_csv_upload` in `db-functions.php`) was corrected to target the user table and includes basic validation.
    *   The `uninstall.php` script was corrected to properly remove all plugin-specific database tables.

These changes establish a more robust and scalable foundation for the test platform.

---

## Evaluation for Monetization & AI Goal (Ruthless)

The current plugin is a **very basic foundation** and **far from the goal** of a top-notch, monetizable, AI-powered test prep platform.

*   **AI Integration (USP):** **Non-Existent.** Relies solely on manual input, failing the core USP.
*   **Monetization Readiness:** **Extremely Poor.** Basic `plan` field exists, but no plan management, access control, or payment integration. Cannot be monetized.
*   **Core Functionality:** **Incomplete.** Test submission, grading, results, and feedback loop is missing or unclear. Admin UI for defining tests from the question bank is absent.
*   **User Experience (UX/UI):** **Rudimentary.** Frontend members' area uses static placeholders. Admin UI is basic forms/tables. Lacks polish and dynamic interaction.
*   **Database Design & Scalability:** **Poor.** Fixed-column structure for tests/responses is inflexible and inefficient. Requires a relational redesign.
*   **Code Quality & Maintainability:** **Mediocre.** Contains known bugs, lacks robust error handling, and has incomplete features. Will be difficult to maintain/extend without refactoring.
*   **Performance Tracking by Category:** **Non-Existent.** Although the database stores question categories (subject/skill) and has fields for summary stats (best/worst subject), there's no logic to calculate these stats or display performance breakdowns to users, failing a key requirement for targeted study guidance.
*   **Security:** Basic measures are present, but insufficient for a commercial product handling user data and payments.

**Conclusion:** Requires substantial development across all areas – core functionality, AI integration, monetization features, UX/UI, code quality, database design, and **especially performance analytics** – to meet the stated goal.

---

## TODO List Towards Monetizable AI Platform

**[x](Phase 1: Core Test Platform Functionality)**

1.  **Refactor Database Schema:** Redesign `tests` and `responses` tables for relational structure. Update all DB functions.
2.  **Implement Test Definition UI:** Admin interface to create tests by selecting questions.
3.  **Implement Full Test Taking Flow:** Robust JS/AJAX for answer submission, saving progress, grading triggers.
4.  **Implement Grading & Performance Analysis:** Backend grading logic, calculate overall scores AND performance stats per `subjectarea`/`skill`. Update aggregate stats in `wp_practice_test_subs`.
5.  **Implement Basic Results Display:** Show overall score and correct/incorrect answers in members' area.
6.  **Fix Existing Bugs:** Correct user CSV upload and uninstall script issues.

**(Phase 2: AI Integration - The USP)**

7.  **Develop/Integrate AI Question Generation:** Build or integrate AI service/API. Create admin UI for triggering/reviewing AI-generated questions.

**(Phase 3: Monetization)**

8.  **Implement Plans & Access Control:** Admin UI for plan definition (Free/Premium, limits). Enforce access control based on user plan. Implement upgrade flow. Make `[practice_tests_plan]` functional.
9.  **Integrate Payment Gateway:** Integrate with Stripe/PayPal etc. for subscription handling.

**(Phase 4: UX/UI & Advanced Features)**

10. **Implement Detailed Performance Display:** Create views in the members' area to show users their performance breakdown by `subjectarea` and `skill` (accuracy, timing if implemented).
11. **Enhance Members Area:** Dynamic content loading (AJAX), professional styling.
12. **Improve Admin Interface:** Better sorting/searching/filtering, inline editing.
13. **Develop Advanced Feedback:** Insightful feedback beyond correct/incorrect (e.g., link to resources based on weak skills).
14. **Guardian/Student Linking:** Allow guardians to view linked student progress and detailed performance breakdowns.

**(Phase 5: Quality & Polish)**

15. **Refactor Codebase:** Improve structure, comments, standards adherence, error handling, add tests.
16. **Security Audit & Hardening:** Thorough security review and implementation.
17. **Performance Optimization:** Analyze and optimize DB queries and code, especially for performance analysis calculations.
18. **Documentation:** Comprehensive admin and user docs.
