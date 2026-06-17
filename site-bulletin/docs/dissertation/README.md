# Dissertation Documentation Pack

This folder contains dissertation-focused technical documentation for the Site Bulletin Laravel application. It is written to support architecture explanation, data-flow discussion, and diagram generation for the dissertation.

The documentation treats the application as a production-style internal operations portal used by thousands of daily users. It therefore explains both the functional behaviour and the engineering decisions used to keep the system maintainable at higher data volume.

## Files

- `architecture-overview.md`
  - High-level application architecture, user roles, bounded contexts, request lifecycle, and deployment/runtime assumptions.

- `service-catalog.md`
  - Detailed catalogue of controllers, services, policies, requests, scheduled commands, and background processing responsibilities.

- `data-flow-and-processing.md`
  - Detailed data-flow documentation for tickets, announcements, messages, analytics, notifications, and the performance sample rollup pipeline.

- `prism-diagram-brief.md`
  - A single prompt-friendly file intended for OpenAI Prism or another research-writing/diagram assistant. It contains diagram instructions, Mermaid source blocks, chart descriptions, and dissertation figure captions.

## How To Use In The Dissertation

Suggested dissertation section mapping:

- System Design / Architecture:
  - Use `architecture-overview.md`.
  - Convert the "Layered Architecture" and "Primary Request Lifecycle" sections into architecture diagrams.

- Implementation:
  - Use `service-catalog.md`.
  - Discuss how service classes separate business rules from controllers.

- Data Processing / Data Management:
  - Use `data-flow-and-processing.md`.
  - Focus on `performance_samples`, `performance_rollups`, `performance_snapshots`, scheduled aggregation, and retention.

- Evaluation / Complexity:
  - Use `data-flow-and-processing.md` and `prism-diagram-brief.md`.
  - Emphasise role-based access control, SLA automation, auditability, and scalable telemetry aggregation.

## Notes About Prism

The user's requested "Prisma AI" appears to refer to OpenAI Prism, a scientific writing workspace. These files are plain Markdown so they can be copied into Prism, used as source context, or converted into LaTeX sections. The `prism-diagram-brief.md` file is intentionally prompt-oriented and diagram-heavy.
