## 2026-03-22 - Decorative Emojis in Links and Buttons
**Learning:** Emojis used as decorative icons alongside clear text (like "↩️ Home" or "Submit ↦") are read aloud by screen readers (e.g., "Leftwards arrow with hook Home"), which creates a confusing and verbose experience for users relying on assistive technology.
**Action:** Always wrap decorative emojis or symbols in `<span aria-hidden="true">` when they are accompanied by descriptive text, ensuring they are ignored by screen readers while remaining visible to sighted users.
