# Edge Cases

## Listing Domain

Approved resolutions below are summarized from `docs/architecture/LISTING_DOMAIN_ARCHITECTURE.md`.

- **Same Owner across Organizations:** treat as independent Organization-scoped Owner records; do not merge or disclose cross-Organization existence.
- **Same Owner within one Organization:** the existing Owner record may be reused for multiple Listings.
- **Standalone property:** Developer, Project/Compound, and Phase may all be absent; usable location remains required.
- **Unlisted Unit Type:** accept a custom `Other / Not Listed` value without adding it automatically to the global catalog.
- **Unlisted Developer, Project, or location:** proposed values must not block Listing creation and may undergo later catalog review.
- **Authorized Admin self-approval:** permitted when the Admin holds the required capability; record both actions in audit history.
- **Partner Agency approval:** an authorized Partner Agency approver may approve normal publication or sold confirmation without mandatory Parent Franchise approval.
- **Owner rejection:** preserve `OWNER_REJECTED` history; allow revision and resubmission through appropriate internal review.
- **Material revision:** invalidates current Owner approval and requires internal review plus renewed Owner approval.
- **Hold without Request:** permitted as a manual hold with recorded reason/context.
- **External sale:** winning Request is absent; never fabricate one.
- **Withdrawn Listing returns:** reuse the existing Listing/history and repeat current review/Owner approval before availability.
- **Assigned Sales User leaves:** an Organization-originated Listing is not a transfer candidate merely because the assigned User leaves; reassign responsibility internally.
- **Originating Sales User leaves:** the Listing may become a transfer candidate, but transfer is not automatic and requires source Organization approval.
