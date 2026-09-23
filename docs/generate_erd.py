#!/usr/bin/env python3
"""Generate a draw.io ERD with orthogonal corridors that avoid tables."""

from pathlib import Path
from xml.sax.saxutils import escape

HEADER = 26
ROW = 18
W_DEFAULT = 250


def h(n: int) -> int:
    return HEADER + ROW * n


TABLES = {
    "departments": {
        "x": 40, "y": 80, "w": 250,
        "fill": "#d5e8d4", "stroke": "#82b366",
        "cols": [
            ("PK", "id"),
            (None, "name"),
            (None, "acronym"),
            (None, "color"),
            (None, "created_at / updated_at"),
        ],
    },
    "courses": {
        "x": 40, "y": 250, "w": 250,
        "fill": "#d5e8d4", "stroke": "#82b366",
        "cols": [
            ("PK", "id"),
            ("FK", "department_id"),
            (None, "name"),
            (None, "duration_years"),
            (None, "created_at / updated_at"),
        ],
    },
    "year_levels": {
        "x": 40, "y": 430, "w": 250,
        "fill": "#d5e8d4", "stroke": "#82b366",
        "cols": [
            ("PK", "id"),
            (None, "name"),
            (None, "sort_order"),
            (None, "created_at / updated_at"),
        ],
    },
    "users": {
        "x": 370, "y": 150, "w": 300,
        "fill": "#dae8fc", "stroke": "#6c8ebf",
        "cols": [
            ("PK", "id"),
            (None, "name"),
            (None, "email / contact_email"),
            (None, "role"),
            (None, "student_id_number / voter_id_number"),
            ("FK", "department_id"),
            ("FK", "course_id"),
            ("FK", "year_level_id"),
            (None, "ocr_name / ocr_student_id / ocr_course"),
            (None, "fraud_score / image_quality"),
            (None, "is_verified / verified_at"),
            (None, "registration_status"),
            (None, "account_expires_at / is_expired / is_disabled"),
            (None, "created_at / updated_at"),
        ],
    },
    "elections": {
        "x": 780, "y": 40, "w": 260,
        "fill": "#fff2cc", "stroke": "#d6b656",
        "cols": [
            ("PK", "id"),
            (None, "title / description"),
            (None, "event_starts_at / event_ends_at"),
            (None, "voting_starts_at / voting_ends_at"),
            (None, "status"),
            ("FK", "created_by → users.id"),
            (None, "created_at / updated_at"),
        ],
    },
    "positions": {
        "x": 1090, "y": 40, "w": 230,
        "fill": "#fff2cc", "stroke": "#d6b656",
        "cols": [
            ("PK", "id"),
            (None, "name"),
            (None, "sort_order"),
            (None, "created_at / updated_at"),
        ],
    },
    "partylists": {
        "x": 1360, "y": 40, "w": 240,
        "fill": "#fff2cc", "stroke": "#d6b656",
        "cols": [
            ("PK", "id"),
            (None, "name"),
            (None, "acronym"),
            (None, "description"),
            (None, "created_at / updated_at"),
        ],
    },
    "candidates": {
        "x": 780, "y": 330, "w": 280,
        "fill": "#fff2cc", "stroke": "#d6b656",
        "cols": [
            ("PK", "id"),
            ("FK", "election_id"),
            (None, "name"),
            ("FK", "position_id"),
            ("FK", "department_id"),
            ("FK", "course_id"),
            ("FK", "partylist_id"),
            (None, "platform / photo_path"),
            (None, "created_at / updated_at"),
        ],
    },
    "announcements": {
        "x": 1360, "y": 330, "w": 250,
        "fill": "#e1d5e7", "stroke": "#9673a6",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            (None, "title / body"),
            (None, "links / image_paths"),
            (None, "created_at / updated_at"),
        ],
    },
    "registration_attempts": {
        "x": 40, "y": 680, "w": 250,
        "fill": "#f8cecc", "stroke": "#b85450",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            (None, "action"),
            (None, "device_fingerprint"),
            (None, "ip_address"),
            (None, "created_at"),
        ],
    },
    "reactivation_requests": {
        "x": 720, "y": 680, "w": 280,
        "fill": "#f8cecc", "stroke": "#b85450",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            (None, "voter_id_number / full_name"),
            (None, "year_stopped / reason"),
            (None, "reactivation_number / status"),
            (None, "duration_years_added"),
            ("FK", "processed_by → users.id"),
            (None, "processed_at"),
            (None, "created_at / updated_at"),
        ],
    },
    "year_level_appeals": {
        "x": 1030, "y": 680, "w": 260,
        "fill": "#f8cecc", "stroke": "#b85450",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            (None, "school_year_start"),
            (None, "reason / status"),
            ("FK", "processed_by → users.id"),
            (None, "processed_at"),
            (None, "created_at / updated_at"),
        ],
    },
    "voter_presences": {
        "x": 1320, "y": 680, "w": 230,
        "fill": "#f8cecc", "stroke": "#b85450",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            (None, "device"),
            (None, "last_seen_at"),
            (None, "created_at / updated_at"),
        ],
    },
    "committee_page_permissions": {
        "x": 1580, "y": 680, "w": 250,
        "fill": "#f8cecc", "stroke": "#b85450",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            (None, "page_key"),
            (None, "created_at / updated_at"),
        ],
    },
    "support_tickets": {
        "x": 1860, "y": 680, "w": 280,
        "fill": "#e1d5e7", "stroke": "#9673a6",
        "cols": [
            ("PK", "id"),
            (None, "ticket_number"),
            ("FK", "user_id"),
            ("FK", "assigned_to → users.id"),
            ("FK", "approved_by → users.id"),
            (None, "subject / category / status"),
            (None, "approved_at / closed_at"),
            (None, "last_message_at"),
            (None, "created_at / updated_at"),
        ],
    },
    "votes": {
        "x": 370, "y": 1060, "w": 250,
        "fill": "#ffe6cc", "stroke": "#d79b00",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            ("FK", "election_id"),
            ("FK", "candidate_id"),
            ("FK", "position_id"),
            (None, "created_at / updated_at"),
        ],
    },
    "ballot_receipts": {
        "x": 660, "y": 1060, "w": 260,
        "fill": "#ffe6cc", "stroke": "#d79b00",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            ("FK", "election_id"),
            (None, "receipt_number"),
            (None, "submitted_at"),
            (None, "created_at / updated_at"),
        ],
    },
    "ballot_submissions": {
        "x": 960, "y": 1060, "w": 280,
        "fill": "#ffe6cc", "stroke": "#d79b00",
        "cols": [
            ("PK", "id"),
            ("FK", "user_id"),
            ("FK", "election_id"),
            (None, "selections / status"),
            ("FK", "ballot_receipt_id"),
            (None, "error_message"),
            (None, "queued_at / processed_at"),
            (None, "created_at / updated_at"),
        ],
    },
    "support_messages": {
        "x": 1860, "y": 1060, "w": 280,
        "fill": "#e1d5e7", "stroke": "#9673a6",
        "cols": [
            ("PK", "id"),
            ("FK", "support_ticket_id"),
            ("FK", "user_id"),
            (None, "body"),
            (None, "created_at / updated_at"),
        ],
    },
    "location_range_settings": {
        "x": 40, "y": 1420, "w": 230,
        "fill": "#f5f5f5", "stroke": "#666666",
        "cols": [
            ("PK", "id"),
            (None, "is_enabled"),
            (None, "latitude / longitude"),
            (None, "range_meters"),
        ],
    },
    "dts_registration_settings": {
        "x": 290, "y": 1420, "w": 230,
        "fill": "#f5f5f5", "stroke": "#666666",
        "cols": [
            ("PK", "id"),
            (None, "is_enabled"),
            (None, "starts_at / ends_at"),
        ],
    },
    "ua_management_settings": {
        "x": 540, "y": 1420, "w": 250,
        "fill": "#f5f5f5", "stroke": "#666666",
        "cols": [
            ("PK", "id"),
            (None, "is_enabled"),
            (None, "idle_seconds / countdown_seconds"),
            (None, "sound_enabled"),
        ],
    },
    "school_year_settings": {
        "x": 810, "y": 1420, "w": 270,
        "fill": "#f5f5f5", "stroke": "#666666",
        "cols": [
            ("PK", "id"),
            (None, "start_year / end_year"),
            (None, "allow_year_level_edit"),
            (None, "year_level_edit_starts_at"),
            (None, "year_level_edit_ends_at"),
        ],
    },
    "gallery_settings": {
        "x": 1100, "y": 1420, "w": 200,
        "fill": "#f5f5f5", "stroke": "#666666",
        "cols": [
            ("PK", "id"),
            (None, "style"),
        ],
    },
    "gallery_images": {
        "x": 1320, "y": 1420, "w": 200,
        "fill": "#f5f5f5", "stroke": "#666666",
        "cols": [
            ("PK", "id"),
            (None, "image_path"),
            (None, "sort_order"),
        ],
    },
    "ssc_member_images": {
        "x": 1540, "y": 1420, "w": 200,
        "fill": "#f5f5f5", "stroke": "#666666",
        "cols": [
            ("PK", "id"),
            (None, "image_path"),
            (None, "sort_order"),
        ],
    },
    "voter_presence_snapshots": {
        "x": 1760, "y": 1420, "w": 230,
        "fill": "#f5f5f5", "stroke": "#666666",
        "cols": [
            ("PK", "id"),
            (None, "recorded_at"),
            (None, "online_total / mobile / desktop"),
        ],
    },
}

for name, t in TABLES.items():
    t["h"] = h(len(t["cols"]))
    t["r"] = t["x"] + t["w"]
    t["b"] = t["y"] + t["h"]
    t["cx"] = t["x"] + t["w"] / 2
    t["cy"] = t["y"] + t["h"] / 2


def T(name: str) -> dict:
    return TABLES[name]


U = T("users")
D = T("departments")
C = T("courses")
Y = T("year_levels")
E = T("elections")
P = T("positions")
PL = T("partylists")
CA = T("candidates")
A = T("announcements")
RA = T("registration_attempts")
RR = T("reactivation_requests")
YA = T("year_level_appeals")
VP = T("voter_presences")
CP = T("committee_page_permissions")
ST = T("support_tickets")
V = T("votes")
BR = T("ballot_receipts")
BS = T("ballot_submissions")
SM = T("support_messages")

# Separate corridors so no two relationships share a path
X_LEFT4 = 6
X_LEFT = 16
X_LEFT2 = 26
X_LEFT3 = 36
X_DU = 304
X_CU = 322
X_YU = 340
X_DC = 352
X_CC = 366
X_CREATED = 676
X_ANNOUNCE_SHAFT = 688
X_CAND_VOTES = 700
X_MSG = 712
X_ACAD_CAND = 728
X_ACAD_CAND2 = 744
X_RIGHT = 2170
X_RIGHT2 = 2188
X_RIGHT3 = 2206
X_RIGHT4 = 2224
X_RIGHT5 = 2242
X_RIGHT6 = 2260
Y_TOP_1 = 10
Y_TOP_2 = 20
Y_TOP_3 = 30
Y_ELEC = 286
Y_ELEC2 = 300
Y_SAT_RA = 536
Y_SAT_RR = 550
Y_SAT_YA = 564
Y_SAT_VP = 578
Y_SAT_CP = 592
Y_SAT_ST = 606
Y_PB_1 = 632
Y_PB_2 = 646
Y_PB_3 = 660
Y_PB_4 = 674
Y_POS_V = 888
Y_EL_V = 904
Y_EL_BR = 920
Y_EL_BS = 936
Y_U_V = 952
Y_U_BR = 968
Y_U_BS = 984
Y_CA_V = 1000
Y_U_SM = 1016

# Unique exits along the bottom of users (370–670)
UX_RA, UX_V, UX_BR, UX_BS, UX_RR, UX_YA, UX_VP, UX_CP, UX_ST = (
    392, 422, 452, 482, 512, 542, 572, 602, 632,
)

# Users-left exits in the gaps beside departments/courses/year_levels
U_LEFT_1 = 211
U_LEFT_2 = 228
U_LEFT_3 = 389
U_LEFT_4 = 406


def frac(table: dict, x: float, y: float) -> tuple[float, float]:
    return (
        (x - table["x"]) / table["w"],
        (y - table["y"]) / table["h"],
    )


# Orthogonal relationships. Each edge has its own color and unshared waypoints.
# Tuple: src, dst, start_card, end_card, label, exit, entry, waypoints, color
RELATIONS = [
    ("departments", "courses", "1", "N", "department_id",
     (0.5, 1), (0.5, 0), [], "#2e7d32"),
    ("departments", "users", "1", "N", "department_id",
     frac(D, D["r"], D["cy"]), frac(U, U["x"], U["y"] + 90),
     [(X_DU, D["cy"]), (X_DU, U["y"] + 90)], "#43a047"),
    ("courses", "users", "1", "N", "course_id",
     frac(C, C["r"], C["cy"]), frac(U, U["x"], U["y"] + 160),
     [(X_CU, C["cy"]), (X_CU, U["y"] + 160)], "#00897b"),
    ("year_levels", "users", "1", "N", "year_level_id",
     frac(Y, Y["r"], Y["cy"]), frac(U, U["x"], U["b"] - 40),
     [(X_YU, Y["cy"]), (X_YU, U["b"] - 40)], "#1565c0"),

    ("departments", "candidates", "1", "0..N", "department_id",
     frac(D, D["r"], D["cy"] - 16), frac(CA, CA["x"] + 70, CA["y"]),
     [(X_DC, D["cy"] - 16), (X_DC, Y_TOP_2), (X_ACAD_CAND, Y_TOP_2),
      (X_ACAD_CAND, Y_ELEC), (CA["x"] + 70, Y_ELEC)], "#7cb342"),
    ("courses", "candidates", "1", "0..N", "course_id",
     frac(C, C["r"], C["cy"] + 16), frac(CA, CA["x"] + 130, CA["y"]),
     [(X_CC, C["cy"] + 16), (X_CC, Y_TOP_1), (X_ACAD_CAND2, Y_TOP_1),
      (X_ACAD_CAND2, Y_ELEC2), (CA["x"] + 130, Y_ELEC2)], "#26a69a"),

    ("users", "elections", "1", "0..N", "created_by",
     frac(U, U["r"], U["y"] + 30), frac(E, E["x"], E["cy"]),
     [(X_CREATED, U["y"] + 30), (X_CREATED, E["cy"])], "#6a1b9a"),
    ("elections", "candidates", "1", "N", "election_id",
     frac(E, E["cx"], E["b"]), frac(CA, E["cx"], CA["y"]),
     [(E["cx"], (E["b"] + CA["y"]) // 2)], "#ef6c00"),
    ("positions", "candidates", "1", "0..N", "position_id",
     (0.5, 1), frac(CA, CA["r"], CA["y"] + 20),
     [(P["cx"], Y_ELEC), (CA["r"], Y_ELEC)], "#c62828"),
    ("partylists", "candidates", "1", "0..N", "partylist_id",
     (0.5, 1), frac(CA, CA["r"], CA["y"] + 50),
     [(PL["cx"], Y_ELEC2), (CA["r"], Y_ELEC2)], "#ad1457"),

    ("users", "registration_attempts", "1", "0..N", "user_id",
     frac(U, UX_RA, U["b"]), (0.5, 0),
     [(UX_RA, Y_SAT_RA), (RA["cx"], Y_SAT_RA)], "#1e88e5"),
    ("users", "reactivation_requests", "1", "0..N", "user_id",
     frac(U, UX_RR, U["b"]), (0.5, 0),
     [(UX_RR, Y_SAT_RR), (RR["cx"], Y_SAT_RR)], "#43a047"),
    ("users", "year_level_appeals", "1", "N", "user_id",
     frac(U, UX_YA, U["b"]), (0.5, 0),
     [(UX_YA, Y_SAT_YA), (YA["cx"], Y_SAT_YA)], "#fb8c00"),
    ("users", "voter_presences", "1", "0..1", "user_id",
     frac(U, UX_VP, U["b"]), (0.5, 0),
     [(UX_VP, Y_SAT_VP), (VP["cx"], Y_SAT_VP)], "#8e24aa"),
    ("users", "committee_page_permissions", "1", "N", "user_id",
     frac(U, UX_CP, U["b"]), (0.5, 0),
     [(UX_CP, Y_SAT_CP), (CP["cx"], Y_SAT_CP)], "#3949ab"),
    ("users", "support_tickets", "1", "N", "user_id",
     frac(U, UX_ST, U["b"]), (0.5, 0),
     [(UX_ST, Y_SAT_ST), (ST["cx"], Y_SAT_ST)], "#d81b60"),
    ("users", "announcements", "1", "N", "user_id",
     frac(U, U["r"], U["y"] + 50), frac(A, A["x"], A["y"] + 24),
     [(X_ANNOUNCE_SHAFT, U["y"] + 50), (X_ANNOUNCE_SHAFT, Y_ELEC), (A["x"], Y_ELEC)], "#5e35b1"),

    ("users", "reactivation_requests", "1", "0..N", "processed_by",
     frac(U, U["x"], U_LEFT_1), frac(RR, RR["x"], RR["cy"]),
     [(X_LEFT, U_LEFT_1), (X_LEFT, Y_PB_1), (RR["x"], Y_PB_1)], "#6d4c41"),
    ("users", "year_level_appeals", "1", "0..N", "processed_by",
     frac(U, U["x"], U_LEFT_2), frac(YA, YA["x"], YA["cy"]),
     [(X_LEFT2, U_LEFT_2), (X_LEFT2, Y_PB_2), (YA["x"], Y_PB_2)], "#8d6e63"),
    ("users", "support_tickets", "1", "0..N", "assigned_to",
     frac(U, U["x"], U_LEFT_3), frac(ST, ST["r"], ST["y"] + 70),
     [(X_LEFT3, U_LEFT_3), (X_LEFT3, Y_PB_3), (X_RIGHT, Y_PB_3), (X_RIGHT, ST["y"] + 70)], "#4527a0"),
    ("users", "support_tickets", "1", "0..N", "approved_by",
     frac(U, U["x"], U_LEFT_4), frac(ST, ST["r"], ST["y"] + 110),
     [(X_LEFT4, U_LEFT_4), (X_LEFT4, Y_PB_4), (X_RIGHT2, Y_PB_4), (X_RIGHT2, ST["y"] + 110)], "#00695c"),

    ("users", "votes", "1", "N", "user_id",
     frac(U, UX_V, U["b"]), frac(V, 400, V["y"]),
     [(UX_V, Y_U_V), (400, Y_U_V)], "#00897b"),
    ("users", "ballot_receipts", "1", "N", "user_id",
     frac(U, UX_BR, U["b"]), frac(BR, 730, BR["y"]),
     [(UX_BR, Y_U_BR), (730, Y_U_BR)], "#6d4c41"),
    ("users", "ballot_submissions", "1", "N", "user_id",
     frac(U, UX_BS, U["b"]), frac(BS, 1030, BS["y"]),
     [(UX_BS, Y_U_BS), (1030, Y_U_BS)], "#546e7a"),

    ("elections", "votes", "1", "N", "election_id",
     frac(E, E["cx"], E["y"]), frac(V, 455, V["y"]),
     [(E["cx"], Y_TOP_2), (X_RIGHT3, Y_TOP_2), (X_RIGHT3, Y_EL_V), (455, Y_EL_V)], "#ef6c00"),
    ("elections", "ballot_receipts", "1", "N", "election_id",
     frac(E, E["cx"] + 20, E["y"]), frac(BR, 820, BR["y"]),
     [(E["cx"] + 20, Y_TOP_3), (X_RIGHT4, Y_TOP_3), (X_RIGHT4, Y_EL_BR), (820, Y_EL_BR)], "#f9a825"),
    ("elections", "ballot_submissions", "1", "N", "election_id",
     frac(E, E["cx"] + 40, E["y"]), frac(BS, 1140, BS["y"]),
     [(E["cx"] + 40, 8), (X_RIGHT5, 8), (X_RIGHT5, Y_EL_BS), (1140, Y_EL_BS)], "#ff8f00"),

    ("candidates", "votes", "1", "N", "candidate_id",
     frac(CA, CA["x"], CA["b"]), frac(V, 510, V["y"]),
     [(X_CAND_VOTES, CA["b"]), (X_CAND_VOTES, Y_CA_V), (510, Y_CA_V)], "#e65100"),
    ("positions", "votes", "1", "N", "position_id",
     (0.5, 0), frac(V, 565, V["y"]),
     [(P["cx"], 8), (X_RIGHT6, 8), (X_RIGHT6, Y_POS_V), (565, Y_POS_V)], "#b71c1c"),

    ("ballot_receipts", "ballot_submissions", "1", "0..1", "ballot_receipt_id",
     frac(BR, BR["r"], BR["cy"]), frac(BS, BS["x"], BR["cy"]),
     [], "#37474f"),

    ("support_tickets", "support_messages", "1", "N", "support_ticket_id",
     (0.5, 1), (0.5, 0), [], "#7b1fa2"),
    ("users", "support_messages", "1", "N", "user_id",
     frac(U, U["r"], U["b"] - 20), frac(SM, 1930, SM["y"]),
     [(X_MSG, U["b"] - 20), (X_MSG, Y_U_SM), (1930, Y_U_SM)], "#c62828"),
]


LABELS = [
    (40, 48, "Academic"),
    (370, 118, "Identity"),
    (780, 16, "Elections"),
    (40, 650, "Voter services"),
    (370, 1030, "Ballots"),
    (1860, 650, "Support"),
    (40, 1390, "Configuration (no foreign keys)"),
]


def row_style(kind: str | None) -> str:
    if kind == "PK":
        fill = "#fff2cc"
    elif kind == "FK":
        fill = "#f8cecc"
    else:
        fill = "none"
    return (
        "text;strokeColor=none;fillColor="
        + fill
        + ";align=left;verticalAlign=middle;spacingLeft=6;spacingRight=4;"
        "overflow=hidden;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;"
        "rotatable=0;whiteSpace=wrap;html=1;fontSize=11;"
    )


def table_style(fill: str, stroke: str) -> str:
    return (
        "swimlane;fontStyle=1;align=center;verticalAlign=top;childLayout=stackLayout;"
        "horizontal=1;startSize=26;horizontalStack=0;resizeParent=1;resizeParentMax=0;"
        "resizeLast=0;collapsible=0;marginBottom=0;whiteSpace=wrap;html=1;fontSize=12;"
        f"fillColor={fill};strokeColor={stroke};strokeWidth=1.4;fontColor=#1a1a1a;"
    )


def arrow(card: str, start: bool) -> str:
    mapping = {
        "1": "ERmandOne",
        "0..1": "ERzeroToOne",
        "N": "ERzeroToMany",
        "0..N": "ERzeroToMany",
        "1..N": "ERoneToMany",
    }
    key = "startArrow" if start else "endArrow"
    return f"{key}={mapping[card]};"


def edge_style(
    start_card: str,
    end_card: str,
    exit_xy: tuple[float, float],
    entry_xy: tuple[float, float],
    color: str,
) -> str:
    ex, ey = exit_xy
    enx, eny = entry_xy
    return (
        "edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=0;"
        f"html=1;strokeColor={color};strokeWidth=1.6;endFill=0;startFill=0;"
        f"fontSize=11;fontStyle=0;fontColor={color};"
        "labelBackgroundColor=#ffffff;labelBorderColor=#d0d5dd;"
        "spacing=4;spacingLeft=10;spacingRight=10;spacingTop=4;spacingBottom=4;"
        "align=center;verticalAlign=middle;overflow=visible;whiteSpace=wrap;"
        f"exitX={ex};exitY={ey};exitDx=0;exitDy=0;"
        f"entryX={enx};entryY={eny};entryDx=0;entryDy=0;"
        f"{arrow(start_card, True)}{arrow(end_card, False)}"
    )


def abs_port(table: dict, frac_xy: tuple[float, float]) -> tuple[int, int]:
    fx, fy = frac_xy
    return (
        int(round(table["x"] + fx * table["w"])),
        int(round(table["y"] + fy * table["h"])),
    )


def segment_hits_table(
    p1: tuple[float, float],
    p2: tuple[float, float],
    table: dict,
    pad: float = 2.0,
) -> bool:
    x1, y1 = p1
    x2, y2 = p2
    left, top = table["x"] + pad, table["y"] + pad
    right, bottom = table["r"] - pad, table["b"] - pad
    if abs(x1 - x2) <= 2:
        x = (x1 + x2) / 2
        lo, hi = sorted((y1, y2))
        return left < x < right and hi > top and lo < bottom
    if abs(y1 - y2) <= 2:
        y = (y1 + y2) / 2
        lo, hi = sorted((x1, x2))
        return top < y < bottom and hi > left and lo < right
    errors_diagonal = True
    return errors_diagonal


def validate_layout() -> list[str]:
    errors: list[str] = []
    names = list(TABLES)
    for i, a in enumerate(names):
        ta = TABLES[a]
        for b in names[i + 1 :]:
            tb = TABLES[b]
            if ta["x"] < tb["r"] and ta["r"] > tb["x"] and ta["y"] < tb["b"] and ta["b"] > tb["y"]:
                errors.append(f"tables overlap: {a} × {b}")

    for src, dst, _sc, _dc, label, exit_xy, entry_xy, wps, _color in RELATIONS:
        start = abs_port(TABLES[src], exit_xy)
        end = abs_port(TABLES[dst], entry_xy)
        path = [start, *wps, end]
        for p1, p2 in zip(path, path[1:]):
            for name, table in TABLES.items():
                if name in (src, dst):
                    continue
                if segment_hits_table(p1, p2, table):
                    errors.append(
                        f"{src} -> {dst} ({label}) crosses {name} "
                        f"on {p1}-{p2}"
                    )
    return errors


def _dist(a: tuple[float, float], b: tuple[float, float]) -> float:
    return ((a[0] - b[0]) ** 2 + (a[1] - b[1]) ** 2) ** 0.5


def _box_hits_table(cx: float, cy: float, tw: float, th: float) -> str | None:
    left, right = cx - tw / 2, cx + tw / 2
    top, bottom = cy - th / 2, cy + th / 2
    for name, table in TABLES.items():
        if left < table["r"] + 6 and right > table["x"] - 6 and top < table["b"] + 6 and bottom > table["y"] - 6:
            return name
    return None


def place_label(
    src: str,
    dst: str,
    label: str,
    exit_xy: tuple[float, float],
    entry_xy: tuple[float, float],
    wps: list[tuple[int, int]],
) -> tuple[float, int, int]:
    """Put the label on the longest open corridor segment, off the line."""
    path = [abs_port(TABLES[src], exit_xy), *wps, abs_port(TABLES[dst], entry_xy)]
    total = sum(_dist(a, b) for a, b in zip(path, path[1:])) or 1
    tw = max(72, 8 * len(label) + 24)
    th = 24
    best: tuple[float, float, int, int] | None = None  # score, along, ox, oy
    walked = 0.0
    for a, b in zip(path, path[1:]):
        seg = _dist(a, b)
        mx, my = (a[0] + b[0]) / 2, (a[1] + b[1]) / 2
        vertical = abs(a[0] - b[0]) <= 2
        if vertical:
            offsets = [(-int(tw / 2) - 12, 0), (int(tw / 2) + 12, 0), (0, 0)]
        else:
            offsets = [(0, -16), (0, 16), (0, 0)]
        for ox, oy in offsets:
            if _box_hits_table(mx + ox, my + oy, tw, th):
                continue
            score = seg + (40 if (ox, oy) != (0, 0) else 0)
            along = (walked + seg / 2) / total
            if 0.12 <= along <= 0.88 and (best is None or score > best[0]):
                best = (score, along, ox, oy)
        walked += seg
    if best:
        return (round(best[1], 3), best[2], best[3])
    return (0.5, 0, -18)


def points_xml(points: list[tuple[int, int]]) -> str:
    if not points:
        return ""
    inner = "".join(f'<mxPoint x="{int(round(x))}" y="{int(round(y))}"/>' for x, y in points)
    return f'<Array as="points">{inner}</Array>'


def build() -> str:
    cells = ['<mxCell id="0"/>', '<mxCell id="1" parent="0"/>']

    cells.append(
        '<mxCell id="title" value="SSCEVS ERD" '
        'style="text;html=1;fontSize=20;fontStyle=1;align=left;fontColor=#1a1a1a;" '
        'vertex="1" parent="1">'
        '<mxGeometry x="40" y="8" width="280" height="28" as="geometry"/>'
        "</mxCell>"
    )
    cells.append(
        '<mxCell id="subtitle" value="Crow’s-foot · each relationship is its own colored line (not bundled)" '
        'style="text;html=1;fontSize=11;align=left;fontColor=#667085;" '
        'vertex="1" parent="1">'
        '<mxGeometry x="1860" y="8" width="380" height="28" as="geometry"/>'
        "</mxCell>"
    )

    for i, (x, y, text) in enumerate(LABELS):
        cells.append(
            f'<mxCell id="lbl-{i}" value="{escape(text)}" '
            'style="text;html=1;fontSize=12;fontStyle=1;align=left;fontColor=#445066;" '
            'vertex="1" parent="1">'
            f'<mxGeometry x="{x}" y="{y}" width="280" height="18" as="geometry"/>'
            "</mxCell>"
        )

    for name, t in TABLES.items():
        tid = f"t-{name}"
        cells.append(
            f'<mxCell id="{tid}" value="{escape(name)}" '
            f'style="{table_style(t["fill"], t["stroke"])}" vertex="1" parent="1">'
            f'<mxGeometry x="{t["x"]}" y="{t["y"]}" width="{t["w"]}" height="{t["h"]}" as="geometry"/>'
            "</mxCell>"
        )
        for i, (kind, col) in enumerate(t["cols"]):
            prefix = f"{kind}  " if kind else "     "
            value = escape(prefix + col)
            cells.append(
                f'<mxCell id="{tid}-c{i}" value="{value}" style="{row_style(kind)}" '
                f'vertex="1" parent="{tid}">'
                f'<mxGeometry y="{HEADER + i * ROW}" width="{t["w"]}" height="{ROW}" as="geometry"/>'
                "</mxCell>"
            )

    for i, (src, dst, sc, dc, label, exit_xy, entry_xy, pts, color) in enumerate(RELATIONS):
        along, ox, oy = place_label(src, dst, label, exit_xy, entry_xy, pts)
        padded = (
            f"&lt;div style=&quot;padding: 3px 8px; white-space: nowrap;&quot;&gt;"
            f"{escape(label)}&lt;/div&gt;"
        )
        offset = f'<mxPoint as="offset" x="{ox}" y="{oy}"/>'
        cells.append(
            f'<mxCell id="e-{i}" value="{padded}" '
            f'style="{edge_style(sc, dc, exit_xy, entry_xy, color)}" '
            f'edge="1" parent="1" source="t-{src}" target="t-{dst}">'
            f'<mxGeometry x="{along}" y="0" relative="1" as="geometry">'
            f"{points_xml(pts)}{offset}</mxGeometry>"
            "</mxCell>"
        )

    legend_x, legend_y = 1860, 40
    cells.append(
        f'<mxCell id="legend" value="Legend" '
        f'style="{table_style("#ffffff", "#666666")}" vertex="1" parent="1">'
        f'<mxGeometry x="{legend_x}" y="{legend_y}" width="220" height="{h(4)}" as="geometry"/>'
        "</mxCell>"
    )
    for i, (kind, text) in enumerate([
        ("PK", "Primary key"),
        ("FK", "Foreign key"),
        (None, "1 — exactly one"),
        (None, "0..N / N — zero or many"),
    ]):
        cells.append(
            f'<mxCell id="legend-c{i}" value="{escape((kind + "  " if kind else "     ") + text)}" '
            f'style="{row_style(kind)}" vertex="1" parent="legend">'
            f'<mxGeometry y="{HEADER + i * ROW}" width="220" height="{ROW}" as="geometry"/>'
            "</mxCell>"
        )

    root = "\n        ".join(cells)
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<mxfile host="app.diagrams.net" agent="SSCEVS" version="22.1.0" type="device">
  <diagram id="sscevs-erd" name="SSCEVS ERD">
    <mxGraphModel dx="0" dy="0" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="2400" pageHeight="1650" background="#ffffff" math="0" shadow="0">
      <root>
        {root}
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>
"""


def main() -> None:
    errors = validate_layout()
    if errors:
        print("Layout collisions:")
        for err in errors:
            print(f"  - {err}")
        raise SystemExit(1)
    out = Path(__file__).with_name("sscevs-erd.drawio")
    out.write_text(build(), encoding="utf-8")
    print(f"Wrote {out}")


if __name__ == "__main__":
    main()
