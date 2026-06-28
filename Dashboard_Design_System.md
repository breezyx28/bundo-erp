# Enterprise HR Dashboard Design System (Extracted & Reconstructed)

> Source: Analysis of provided TeamHub-style dashboard montage.
> Method: Visual reverse-engineering + industry-standard SaaS dashboard patterns (Ant Design, Material UI, shadcn/ui, Radix UI, Tailwind UI).

---

# 1. Design Philosophy

- Product Type: Enterprise HR / Workforce Management Platform
- Style: Modern SaaS Dashboard
- Density: Medium
- Tone: Professional, friendly, data-centric
- Visual Language:
  - Soft cards
  - Large whitespace
  - Rounded corners
  - Light mint accent
  - Minimal shadows
  - High data readability

---

# 2. Grid System

## Desktop

- Canvas Width: 1440px
- Content Max Width: 1320px
- Left Sidebar: 240px
- Main Content: Flexible
- Right Utility Panel: 280px–320px

### Layout Grid

- 12 Columns
- Column Width: 72px
- Gutter: 24px
- Outer Margin: 32px

### Spacing Scale

| Token | Size |
|---------|---------|
| xs | 4px |
| sm | 8px |
| md | 12px |
| lg | 16px |
| xl | 24px |
| 2xl | 32px |
| 3xl | 40px |
| 4xl | 48px |

---

# 3. Color System

## Primary

Mint Green

- Primary 500: #39C6A0
- Primary 600: #2EB18E
- Primary 700: #228C70

## Secondary

- Light Mint: #EAF8F3
- Surface Mint: #F5FBF8

## Neutrals

- Background: #F7FAF9
- Card: #FFFFFF
- Border: #E7ECEB
- Divider: #EEF2F1

## Text

- Heading: #1D2A2A
- Body: #596969
- Muted: #8D9A9A
- Disabled: #B8C2C2

## Status

Success: #22C55E
Warning: #F59E0B
Danger: #EF4444
Info: #3B82F6

---

# 4. Typography

Font Family

- Inter
- Manrope
- Plus Jakarta Sans

## Headings

H1: 32 / 40 / 700
H2: 28 / 36 / 700
H3: 24 / 32 / 600
H4: 20 / 28 / 600

## Dashboard

Card Title:
16 / 24 / 600

Metric Value:
32 / 36 / 700

Table Text:
14 / 20 / 500

Caption:
12 / 16 / 500

---

# 5. Radius System

Small: 8px
Medium: 12px
Large: 16px
XL: 20px
Pill: 999px

Observed Main Card Radius:
≈ 16px

Buttons:
≈ 12px

Input Fields:
≈ 10–12px

---

# 6. Shadow System

Card Shadow

0 2px 8px rgba(0,0,0,0.04)

Hover

0 8px 24px rgba(0,0,0,0.08)

Dropdown

0 12px 32px rgba(0,0,0,0.12)

---

# 7. Sidebar Navigation

Width: 240px

Structure:

Logo
Primary Navigation
Utility Links
Upgrade Card

Menu Item

Height: 44px
Radius: 12px
Horizontal Padding: 12px

Icon:
20x20

Gap:
12px

Active State

Background:
Primary 500

Text:
White

---

# 8. Top Navigation Bar

Height: 72px

Contains:

Search
Notifications
Settings
Quick Actions
User Avatar

Search Field

Width:
320–420px

Height:
44px

Radius:
12px

---

# 9. Card System

Standard Card

Padding:
24px

Radius:
16px

Border:
1px solid #E7ECEB

Background:
White

Header Height:
40px

Gap Between Cards:
24px

---

# 10. KPI Cards

Typical Size

Width:
220–280px

Height:
110–140px

Structure

Label
Value
Delta Indicator
Mini Visualization

Metric

Font:
32px

Positive Growth Badge

Height:
24px

Radius:
999px

---

# 11. Employee Profile Card

Avatar:
96x96

Name:
18px 600

Role:
14px

Status Badge

Height:
24px

Radius:
999px

Information Grid

2 columns

Gap:
12px

---

# 12. Avatar System

Sizes

XS: 24
SM: 32
MD: 40
LG: 56
XL: 96

Radius:
50%

Group Avatar Stack

Overlap:
-8px

Border:
2px white

---

# 13. Tables

Row Height:
52px

Header Height:
48px

Cell Padding:
16px

Columns

Employee
Department
Position
Status
Date
Actions

Table Radius:
16px

Hover Background:
#F8FAFA

---

# 14. Status Chips

Height:
24px

Padding:
0 12px

Radius:
999px

States

Active
Pending
Approved
Rejected
Remote
Hybrid
Internship

---

# 15. Forms

Input Height:
44px

Textarea:
120px

Radius:
12px

Label Gap:
8px

Section Gap:
24px

Two Column Layout

Field Width:
50%

---

# 16. Calendar Component

Monthly Grid

Cell:
36x36

Radius:
10px

Selected Day

Primary Fill

Today

Outlined Circle

Event Indicator

4px Dot

---

# 17. Donut Charts

Diameter:
80–120px

Stroke Width:
8px

Center Metric

24px / 700

Legend

12px text

---

# 18. Line Charts

Height:
220px

Grid Lines:
Subtle

Stroke:
3px

Area Fill:
5–10% opacity

Tooltip Radius:
12px

---

# 19. Bar Charts

Bar Radius:
6px

Gap:
8px

Chart Height:
220px

Axes:
Minimal

---

# 20. Heatmap Attendance Chart

Cell:
20x20

Radius:
4px

Color Scale

0%
25%
50%
75%
100%

Primary Green Gradient

---

# 21. Recruitment Dashboard

Widgets

Applications
Interviews
Hires
Vacancies
Applicant Sources

Primary Table

6–8 Columns

Schedule Panel

Width:
280px

---

# 22. Attendance Dashboard

Widgets

Present
On Leave
Absent
Late

Employee Attendance Table

Filter Row

Search
Department
Date Range
Export

---

# 23. Employee Directory

Card Width:
220px

Card Height:
260px

Avatar:
72px

Action Button:
Small pill

Grid:
4 columns desktop

---

# 24. Notifications Panel

Width:
320px

Item Height:
56px

Unread Indicator:
8px dot

---

# 25. Mobile Layout

Width:
390px

Sidebar:
Hidden

Top Navigation:
64px

Cards:
Single Column

Spacing:
16px

---

# 26. Component Library Recommendation

Foundation

- Tailwind CSS
- Radix UI
- shadcn/ui

Charts

- Recharts
- Nivo
- ApexCharts

Tables

- TanStack Table

Forms

- React Hook Form
- Zod

Calendar

- React Day Picker

Icons

- Lucide Icons

Avatars

- Radix Avatar

Command Search

- cmdk

---

# 27. Design Tokens

--radius-card: 16px
--radius-input: 12px
--radius-button: 12px

--space-1: 4px
--space-2: 8px
--space-3: 12px
--space-4: 16px
--space-5: 24px
--space-6: 32px

--primary: #39C6A0
--background: #F7FAF9
--surface: #FFFFFF
--border: #E7ECEB

---

# 28. Page Layout Blueprint

Desktop

Top Navbar (72px)

├── Sidebar (240px)
├── Main Content
│   ├── KPI Row
│   ├── Charts Row
│   ├── Table Row
│   └── Secondary Widgets
└── Utility Panel (280-320px)

---

# 29. AI Reconstruction Prompt

Create a premium enterprise HR management dashboard in Figma. Use a 12-column desktop grid (1440px width), 240px left navigation, mint-green SaaS color palette, 16px card radius, 24px spacing rhythm, Inter typography, large KPI cards, employee management tables, recruitment analytics, attendance heatmaps, donut charts, line charts, calendar widgets, avatar groups, status chips, recruitment pipelines, schedule panels and responsive mobile layouts. Follow modern shadcn/ui, Linear, Notion, Stripe Dashboard and Tailwind UI design principles. Use clean whitespace, subtle shadows, soft borders, high information density and enterprise-grade usability.
