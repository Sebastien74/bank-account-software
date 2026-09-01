# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Primary user: the product's author/owner, for personal and family use — tracking their own bank accounts. Not built for external clients or a team; single-household usage.

## Product Purpose

A bank account management tool ("Wallet" domain in the codebase) that imports bank statement/account data and computes balances and related figures automatically, so the user can track their accounts without manual reconciliation.

## Positioning

Import + automatic calculation of account data (balances, reconciliation) is the core differentiator, layered on top of a general-purpose CMS (SFCMS-7) rather than a purpose-built fintech product. Recent core work has focused on correcting the import and calculation mechanics of the accounts module.

## Operating Context

- Back-office admin interface (Symfony back-end) for managing wallet/account data; front templates and admin ("charte-back") screens both exist in the repo.
- Built on Symfony (PHP >=8.3), Doctrine ORM, Webpack Encore/Yarn front-end tooling.
- Local dev via WAMP; DB access documented separately (MariaDB, `bas_`-prefixed tables).

## Capabilities and Constraints

- Domain module: `Wallet` (entities, forms, repositories, commands, enums under `src/*/Wallet`).
- Handles sensitive financial data — bank account and transaction information. Treat as sensitive data requiring careful handling in display, export, and logging, even though no formal regulatory constraint (e.g. RGPD/PCI) has been confirmed as applicable.
- No accessibility standard has been confirmed as mandatory, but accessibility is a stated goal to preserve going forward.
- No formal hosting/compliance constraint confirmed beyond the above.

## Brand Commitments

None confirmed.

## Evidence on Hand

None supplied; no testimonials, case studies, or external proof assets to reference.

## Product Principles

1. Correctness of financial calculations (import, balances, reconciliation) takes priority over visual polish.
2. Sensitive account data should never be over-exposed in the UI (logs, exports, screenshots) beyond what the task needs.
3. Single-user/family context: prioritize clarity and efficiency for a returning, familiar user over onboarding/marketing concerns.
4. Preserve the existing back-office admin visual system (Materio/Vuexy-derived, see `.claude/charte-back/`) rather than introducing a competing style.

## Accessibility & Inclusion

Accessibility is a goal for future design work; no specific standard (e.g. WCAG level) has been confirmed as a requirement yet.
