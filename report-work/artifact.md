# Template execution contract

## Reference

- Source retained unchanged: `/Users/admin/Documents/Du an TN Project Report Template.doc`
- Working conversion: `/Users/admin/Code/DATN_WD50/report-work/template-convert/Du an TN Project Report Template.docx`
- Source format: Microsoft Word binary `.doc`; working format: OOXML `.docx`
- Render evidence: `/Users/admin/Code/DATN_WD50/report-work/template-render`
- Baseline page count: 16; section count: 1

## Page system

- A4 portrait, 8.27 × 11.69 inches.
- Margins: left 1.18 in, right 0.79 in, top 0.79 in, bottom 0.79 in.
- One section, different first-page header enabled.
- Recurring header: FPT Education/FPT Polytechnic artwork at upper left and a blue page-number tab at upper right.
- Recurring footer: horizontal rule and project/group label.

## Typography and components

- Primary font: Times New Roman.
- Part titles: centered, uppercase, bold, approximately 20 pt.
- Level-2 headings: left aligned, bold, approximately 16 pt.
- Level-3 headings: left aligned, regular, approximately 14 pt.
- Body paragraphs: Times New Roman, black, approximately 13 pt, justified, 1.5-line spacing.
- Keep all existing header/footer artwork, pagination, page geometry, and later report sections.

## Slot map

- `word/document.xml`, paragraph beginning `PHẦN 1. GIỚI THIỆU CHUNG`: preserve title.
- Paragraphs from `1. 1 Giới thiệu đề tài` through the content below `1.3.2 Các công nghệ`: replace template prompts with project-specific content.
- `1.2 Thành viên tham gia dự án`: preserve heading and retain an explicit student-fill placeholder.
- `word/document.xml`, paragraph beginning `PHẦN 2. KHẢO SÁT HỆ THỐNG`: preserve title.
- Paragraphs under 2.1, 2.2, and 2.3: replace template prompts with project-specific survey content.
- Paragraph `PHẦN 3. PHÂN TÍCH HỆ THỐNG` and everything after it: preserve.

## Package preservation and fidelity gates

- Preserve styles, numbering, theme, headers, footers, media, relationships, drawings, tables, and all content outside the declared slots.
- Do not modify the retained `.doc` source.
- Final must retain A4 geometry, recurring FPT branding, footer, and the start of Part 3.
- Render every final page; fail on clipping, overlap, broken header/footer, or unexpected loss of later template content.
