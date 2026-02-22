# LPAS — ER & DFD Diagrams (Black and White)

## How to view

Open in a web browser:

- **ER_DFD_DIAGRAMS.html** — Contains all diagrams in one page.

Or from project root:

```
documentation/diagrams/ER_DFD_DIAGRAMS.html
```

## Notation

### ER Diagram · Chen Notation

| Symbol        | Meaning                          |
|---------------|-----------------------------------|
| Rectangle     | Entity                            |
| Oval          | Attribute (simple)                |
| Dashed oval   | Derived attribute                 |
| Diamond       | Relationship                      |
| Underlined    | Primary key attribute             |

Theme: **Black and white** (black strokes and text on white).

### DFD

| Symbol   | Meaning                          |
|----------|-----------------------------------|
| Rectangle| External entity                  |
| Circle   | System process                   |
| Open box | Datastore (top, bottom, left only; **right open**) |
| Arrow    | Data flow                        |

Theme: **Black and white**.

## Contents

1. **ER Diagram** — Entities: USER, STAFF, STUDENT, DEPARTMENT, LAB, SUBJECT, QUESTION, EXAM_SESSION, STUDENT_ALLOCATION, SUBMISSION, RESULT, ACTIVITY_LOG, NOTIFICATION; relationships and key attributes.
2. **DFD Level 0** — Context: one process (Lab Programming Allocation System), external entities Admin, Staff, Student, System Admin.
3. **DFD Level 1** — Processes: 1 AUTH & RBAC, 2 Exam Session & Allocation, 3 Monitor & Evaluate, 4 Reports; datastores User table, Exam_Session, Student_Allocation, Submission, Activity_log.
4. **DFD Level 2** — Expansion of process 2: 2.1 Start Session, 2.2 Allocation Engine, 2.3 Notify Students, 2.4 Student Join Exam, 2.5 Submit Code.
