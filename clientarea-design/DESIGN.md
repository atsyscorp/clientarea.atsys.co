# clientarea DESIGN.md

> Auto-generated design system — reverse-engineered via static analysis by skillui.
> Frameworks: None detected
> Colors: 6 · Fonts: 1 · Components: 4
> Icon library: not detected · State: not detected
> Primary theme: light · Dark mode toggle: no · Motion: expressive

## Visual Reference

**Match this design exactly** — study colors, fonts, spacing, and component shapes before writing any UI code.

![clientarea Homepage](../screenshots/homepage.png)

---

## 1. Visual Theme & Atmosphere

This is a **light-themed** interface with a neutral, approachable feel. The light background emphasizes content clarity. Typography uses **SFMono-Regular** throughout — a technical, developer-focused choice that maintains consistency. Spacing follows a **4px base grid** (compact density), with scale: 2, 4, 6, 8, 10, 12, 14, 16px. Motion is expressive — spring physics, layout animations, and staggered reveals are part of the visual language.

---

## 2. Color Palette & Roles

| Token | Hex | Role | Use |
|---|---|---|---|
| tw-ring-offset-color | `#ffffff` | background | Page background, darkest surface |
| surface | `#9ca3af` | surface | Card and panel backgrounds |
| text-primary | `#000000` | text-primary | Headings and body text |
| success | `#10b981` | success | Success states, positive indicators |
| tw-ring-color | `#3b82f6` | info | Informational highlights |
| unknown | `#134c42` | unknown | Palette color |

### CSS Variable Tokens

```css
--tw-border-spacing-x: 0;
--tw-border-spacing-y: 0;
--tw-border-spacing-x: 0;
--tw-border-spacing-y: 0;
--border-btn: 1px;
--tab-border: 1px;
--border-btn: 1px;
--tab-border: 1px;
--border-btn: 1px;
--tab-border: 1px;
--border-btn: 1px;
--tab-border: 1px;
--tw-border-opacity: 1;
--tw-border-opacity: 1;
--tw-border-opacity: 1;
--tw-border-opacity: 1;
--tw-border-opacity: 1;
--tw-border-opacity: 1;
--tw-border-opacity: 1;
--tw-border-opacity: 1;
```


---

## 3. Typography Rules

**Font Stack:**
- **SFMono-Regular** — Heading 1, Heading 2, Heading 3, Body, Caption, Code

| Role | Font | Size | Weight |
|---|---|---|---|
| Heading 1 | SFMono-Regular | 8rem | 700 |
| Heading 2 | SFMono-Regular | 3.75rem | 700 |
| Heading 3 | SFMono-Regular | 3rem | 700 |
| Body | SFMono-Regular | .875rem | 400 |
| Caption | SFMono-Regular | .75rem | 400 |
| Code | SFMono-Regular | 14px | 400 |

**Typographic Rules:**
- Use **SFMono-Regular** for all text — do not mix font families
- Maintain consistent hierarchy: no more than 3-4 font sizes per screen
- Headings use bold (600-700), body uses regular (400)
- Line height: 1.5 for body text, 1.2 for headings
- Use color and opacity for secondary hierarchy, not additional font sizes


---

## 4. Component Stylings

### Data Display (1)

**Badge** — `html`

### Data Input (2)

**Button** — `html`
- Variants: `primary`
- Animation: 

**Input** — `html`
- State: :focus, :placeholder

### Media (1)

**Icon** — `html`



---

## 5. Layout Principles

- **Base spacing unit:** 4px
- **Spacing scale:** 2, 4, 6, 8, 10, 12, 14, 16, 20, 24, 28, 30
- **Border radius:** inherit, unset, .25rem, .5rem, .75rem, 1rem, 1.5rem, 40px
- **Max content width:** 80rem

**Spacing as Meaning:**
| Spacing | Use |
|---|---|
| 4-8px | Tight: related items within a group |
| 12-16px | Medium: between groups |
| 24-32px | Wide: between sections |
| 48px+ | Vast: major section breaks |


---

## 6. Depth & Elevation

### Flat — subtle depth hints

- `var(--handleoffsetcalculator)0 0 2px var(--tglbg) inset,0 0 0 2px var(--tglbg) inset,var(--togglehandleborder)`
- `2px 2px`
- `calc(var(--handleoffset)/2)0 0 2px var(--tglbg) inset,calc(var(--handleoffset)/-2)0 0 2px var(--tglbg) inset,0 0 0 2px var(--tglbg) inset`

### Raised — cards, buttons, interactive elements

- `0 0 0 4px var(--fallback-b1,oklch(var(--b1)/1)) inset,0 0 0 4px var(--fallback-b1,oklch(var(--b1)/1)) inset`
- `0 0 0 3px var(--fallback-b1,oklch(var(--b1)/1)) inset,0 0 0 3px var(--fallback-b1,oklch(var(--b1)/1)) inset`
- `0 0 0 3px var(--range-shdw) inset,var(--focus-shadow,0 0),calc(var(--filler-size)*-1 - var(--filler-offset))0 0 var(--filler-size)`

### Floating — dropdowns, popovers, modals

- `0 0 0 12px var(--fallback-b1,oklch(var(--b1)/1)) inset,0 0 0 12px var(--fallback-b1,oklch(var(--b1)/1)) inset`

### Overlay — full-screen overlays, top-level dialogs

- `0 25px 50px -12px rgba(0,0,0,.25)`

### Z-Index Scale

`0, 1, 10, 20, 40, 50, 100, 999`



---

## 7. Animation & Motion

This project uses **expressive motion**. Animations are an integral part of the experience.

### CSS Animations

- `@keyframes button-pop`
- `@keyframes checkmark`
- `@keyframes modal-pop`
- `@keyframes progress-loading`
- `@keyframes radiomark`
- `@keyframes rating-pop`
- `@keyframes skeleton`
- `@keyframes toast-pop`

### Animated Components

- **Button**: 

### Motion Guidelines

- Duration: 150-300ms for micro-interactions, 300-500ms for page transitions
- Easing: `ease-out` for enters, `ease-in` for exits
- Always respect `prefers-reduced-motion`


---

## 8. Do's and Don'ts

### Do's

- Use `#ffffff` as the primary page background
- Follow the **4px** spacing grid for all margins, padding, and gaps
- Use the defined shadow tokens for elevation — see Section 6
- Use border-radius from the scale: inherit, unset, .25rem, .5rem, .75rem
- Reuse existing components from Section 4 before creating new ones

### Don'ts

- Don't introduce colors outside this palette — extend the design tokens first
- Don't use arbitrary spacing values — stick to multiples of 4px
- Don't create custom box-shadow values outside the system tokens
- Don't use arbitrary border-radius values — pick from the defined scale
- Don't duplicate component patterns — check Section 4 first
- Don't use backdrop-blur or blur effects

### Anti-Patterns (detected from codebase)

- No blur or backdrop-blur effects
- No zebra striping on tables/lists


---

## 9. Responsive Behavior

| Name | Value | Source |
|---|---|---|
| sm | 640px | css |
| md | 768px | css |
| lg | 1024px | css |
| xl | 1280px | css |
| 2xl | 1536px | css |

**Approach:** Use `@media (min-width: ...)` queries matching the breakpoints above.


---

## 10. Agent Prompt Guide

Use these as starting points when building new UI:

### Build a Card

```
Background: #9ca3af
Border: 1px solid var(--border)
Radius: .75rem
Padding: 16px
Font: SFMono-Regular
Use shadow tokens from Section 6.
```

### Build a Button

```
Primary: bg var(--accent), text white
Ghost: bg transparent, border var(--border)
Padding: 8px 16px
Radius: .75rem
Hover: opacity 0.9 or lighter shade
Focus: ring with var(--accent)
```

### Build a Page Layout

```
Background: #ffffff
Max-width: 80rem, centered
Grid: 4px base
Responsive: mobile-first, breakpoints from Section 9
```

### Build a Stats Card

```
Surface: #9ca3af
Label: var(--text-muted) (muted, 12px, uppercase)
Value: #000000 (primary, 24-32px, bold)
Status: use success/warning/danger from Section 2
```

### Build a Form

```
Input bg: #ffffff
Input border: 1px solid var(--border)
Focus: border-color var(--accent)
Label: var(--text-muted) 12px
Spacing: 16px between fields
Radius: .75rem
```

### General Component

```
1. Read DESIGN.md Sections 2-6 for tokens
2. Colors: only from palette
3. Font: SFMono-Regular, type scale from Section 3
4. Spacing: 4px grid
5. Components: match patterns from Section 4
6. Elevation: shadow tokens
```
