#!/usr/bin/env python3
"""Generate a draw.io flowchart of the main SSCEVS user and committee flows."""

from pathlib import Path
from xml.sax.saxutils import escape

# Shape styles
START = (
    "ellipse;whiteSpace=wrap;html=1;aspect=fixed;fontSize=12;fontStyle=1;"
    "fillColor=#d5e8d4;strokeColor=#82b366;strokeWidth=1.4;"
)
END = (
    "ellipse;whiteSpace=wrap;html=1;aspect=fixed;fontSize=12;fontStyle=1;"
    "fillColor=#f8cecc;strokeColor=#b85450;strokeWidth=1.4;"
)
PROCESS = (
    "rounded=1;whiteSpace=wrap;html=1;arcSize=12;fontSize=12;"
    "fillColor=#dae8fc;strokeColor=#6c8ebf;strokeWidth=1.4;"
)
PROCESS_GOLD = (
    "rounded=1;whiteSpace=wrap;html=1;arcSize=12;fontSize=12;"
    "fillColor=#fff2cc;strokeColor=#d6b656;strokeWidth=1.4;"
)
PROCESS_PURPLE = (
    "rounded=1;whiteSpace=wrap;html=1;arcSize=12;fontSize=12;"
    "fillColor=#e1d5e7;strokeColor=#9673a6;strokeWidth=1.4;"
)
PROCESS_ORANGE = (
    "rounded=1;whiteSpace=wrap;html=1;arcSize=12;fontSize=12;"
    "fillColor=#ffe6cc;strokeColor=#d79b00;strokeWidth=1.4;"
)
DECISION = (
    "rhombus;whiteSpace=wrap;html=1;fontSize=11;"
    "fillColor=#fff2cc;strokeColor=#d6b656;strokeWidth=1.4;"
)
LANE = (
    "swimlane;horizontal=0;startSize=36;fontStyle=1;fontSize=14;"
    "fillColor=#f8fafc;strokeColor=#94a3b8;strokeWidth=1.2;"
    "whiteSpace=wrap;html=1;collapsible=0;"
)
TITLE = "text;html=1;fontSize=20;fontStyle=1;align=left;fontColor=#1a1a1a;"
SUB = "text;html=1;fontSize=11;align=left;fontColor=#667085;"
EDGE = (
    "edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=0;"
    "html=1;strokeColor=#445066;strokeWidth=1.5;endArrow=block;endFill=1;"
    "fontSize=11;fontColor=#334155;labelBackgroundColor=#ffffff;"
    "labelBorderColor=#d0d5dd;spacingLeft=8;spacingRight=8;spacingTop=3;"
    "spacingBottom=3;align=center;verticalAlign=middle;overflow=visible;"
)


def node(nid: str, value: str, style: str, x: int, y: int, w: int, h: int, parent: str = "1") -> str:
    return (
        f'<mxCell id="{nid}" value="{escape(value)}" style="{style}" '
        f'vertex="1" parent="{parent}">'
        f'<mxGeometry x="{x}" y="{y}" width="{w}" height="{h}" as="geometry"/>'
        "</mxCell>"
    )


def edge(
    eid: str,
    source: str,
    target: str,
    label: str = "",
    exit_xy: tuple[float, float] = (0.5, 1),
    entry_xy: tuple[float, float] = (0.5, 0),
    points: list[tuple[int, int]] | None = None,
    along: float = 0.5,
    ox: int = 0,
    oy: int = -14,
) -> str:
    ex, ey = exit_xy
    enx, eny = entry_xy
    style = (
        f"{EDGE}exitX={ex};exitY={ey};exitDx=0;exitDy=0;"
        f"entryX={enx};entryY={eny};entryDx=0;entryDy=0;"
    )
    pts = ""
    if points:
        inner = "".join(f'<mxPoint x="{x}" y="{y}"/>' for x, y in points)
        pts = f'<Array as="points">{inner}</Array>'
    value = ""
    if label:
        value = (
            f"&lt;div style=&quot;padding: 2px 8px; white-space: nowrap;&quot;&gt;"
            f"{escape(label)}&lt;/div&gt;"
        )
    offset = f'<mxPoint as="offset" x="{ox}" y="{oy}"/>'
    return (
        f'<mxCell id="{eid}" value="{value}" style="{style}" edge="1" '
        f'parent="1" source="{source}" target="{target}">'
        f'<mxGeometry x="{along}" y="0" relative="1" as="geometry">'
        f"{pts}{offset}</mxGeometry>"
        "</mxCell>"
    )


def build() -> str:
    cells: list[str] = ['<mxCell id="0"/>', '<mxCell id="1" parent="0"/>']

    cells.append(node("title", "SSCEVS — System Flowchart", TITLE, 40, 12, 520, 28))
    cells.append(
        node(
            "subtitle",
            "Main voter and committee flows · orthogonal connectors · Yes/No decisions",
            SUB,
            40,
            40,
            720,
            18,
        )
    )

    # ---- Lane frames (visual only; nodes parented to page for clean edges) ----
    cells.append(node("lane-access", "1. Access & Registration", LANE, 30, 70, 340, 1480))
    cells.append(node("lane-login", "2. Login & Account", LANE, 400, 70, 340, 1480))
    cells.append(node("lane-vote", "3. Voting", LANE, 770, 70, 360, 1480))
    cells.append(node("lane-committee", "4. Committee", LANE, 1160, 70, 380, 1480))

    # ==================== 1. Access & Registration ====================
    # Lane content x origin ~ 60
    cells.append(node("a-start", "Start", START, 145, 120, 90, 50))
    cells.append(node("a-loc", "Location gate\nenabled?", DECISION, 110, 200, 160, 90))
    cells.append(node("a-check", "Check GPS\nwithin campus range", PROCESS, 115, 320, 150, 70))
    cells.append(node("a-in", "Within\nrange?", DECISION, 110, 420, 160, 90))
    cells.append(node("a-block", "Block access\nOutside campus", END, 300, 435, 100, 60))
    cells.append(node("a-home", "Welcome / Home", PROCESS, 115, 540, 150, 60))
    cells.append(node("a-reg-open", "Registration\nwindow open?", DECISION, 110, 630, 160, 90))
    cells.append(node("a-closed", "Registration\nclosed", END, 300, 645, 100, 60))
    cells.append(node("a-form", "Fill registration\nform", PROCESS, 115, 750, 150, 60))
    cells.append(node("a-scan", "Scan student ID\n(OCR)", PROCESS, 115, 840, 150, 60))
    cells.append(node("a-otp", "Verify OTP\n(email)", PROCESS, 115, 930, 150, 60))
    cells.append(node("a-email", "Confirm email\nlink", PROCESS, 115, 1020, 150, 60))
    cells.append(node("a-pending", "Account pending\ncommittee review", PROCESS_GOLD, 105, 1110, 170, 60))
    cells.append(node("a-end", "Await verification", END, 140, 1210, 100, 50))

    # Access edges
    cells.append(edge("ea1", "a-start", "a-loc"))
    cells.append(edge("ea2", "a-loc", "a-check", "Yes", (0.5, 1), (0.5, 0), oy=-12))
    cells.append(
        edge(
            "ea3",
            "a-loc",
            "a-home",
            "No",
            (0, 0.5),
            (0, 0.5),
            [(70, 245), (70, 570)],
            along=0.45,
            ox=-18,
            oy=0,
        )
    )
    cells.append(edge("ea4", "a-check", "a-in"))
    cells.append(edge("ea5", "a-in", "a-home", "Yes"))
    cells.append(edge("ea6", "a-in", "a-block", "No", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("ea7", "a-home", "a-reg-open"))
    cells.append(edge("ea8", "a-reg-open", "a-form", "Yes"))
    cells.append(edge("ea9", "a-reg-open", "a-closed", "No", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("ea10", "a-form", "a-scan"))
    cells.append(edge("ea11", "a-scan", "a-otp"))
    cells.append(edge("ea12", "a-otp", "a-email"))
    cells.append(edge("ea13", "a-email", "a-pending"))
    cells.append(edge("ea14", "a-pending", "a-end"))

    # ==================== 2. Login & Account ====================
    cells.append(node("b-start", "Login", START, 525, 120, 90, 50))
    cells.append(node("b-auth", "Credentials\nvalid?", DECISION, 490, 200, 160, 90))
    cells.append(node("b-fail", "Show error\nTry again", END, 690, 215, 100, 60))
    cells.append(node("b-disabled", "Account\ndisabled?", DECISION, 490, 320, 160, 90))
    cells.append(node("b-appeal", "Year-level\nappeal form", PROCESS_PURPLE, 680, 335, 130, 60))
    cells.append(node("b-expired", "Account\nexpired?", DECISION, 490, 440, 160, 90))
    cells.append(node("b-react", "Reactivation\nrequest", PROCESS_PURPLE, 680, 455, 130, 60))
    cells.append(node("b-verified", "Verified by\ncommittee?", DECISION, 490, 560, 160, 90))
    cells.append(node("b-status", "Registration\nstatus page", PROCESS_GOLD, 680, 575, 130, 60))
    cells.append(node("b-dash", "Dashboard", PROCESS, 505, 680, 130, 60))
    cells.append(node("b-profile", "Update profile /\nyear level", PROCESS, 505, 780, 130, 60))
    cells.append(node("b-help", "Open help ticket\n/ FAQ / AIVA", PROCESS_PURPLE, 505, 880, 130, 70))
    cells.append(node("b-end", "Continue as\nvoter", END, 520, 990, 100, 50))

    cells.append(edge("eb1", "b-start", "b-auth"))
    cells.append(edge("eb2", "b-auth", "b-disabled", "Yes"))
    cells.append(edge("eb3", "b-auth", "b-fail", "No", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("eb4", "b-disabled", "b-appeal", "Yes", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("eb5", "b-disabled", "b-expired", "No"))
    cells.append(edge("eb6", "b-expired", "b-react", "Yes", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("eb7", "b-expired", "b-verified", "No"))
    cells.append(edge("eb8", "b-verified", "b-dash", "Yes"))
    cells.append(edge("eb9", "b-verified", "b-status", "No", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("eb10", "b-dash", "b-profile"))
    cells.append(edge("eb11", "b-profile", "b-help"))
    cells.append(edge("eb12", "b-help", "b-end"))
    # Cross-lane: registration end → login
    cells.append(
        edge(
            "ex1",
            "a-end",
            "b-start",
            "After verify",
            (1, 0.5),
            (0, 0.5),
            [(280, 1235), (280, 100), (480, 100), (480, 145)],
            along=0.55,
            ox=0,
            oy=-14,
        )
    )

    # ==================== 3. Voting ====================
    cells.append(node("c-start", "Open Elections", START, 905, 120, 100, 50))
    cells.append(node("c-can", "Verified &\nnot expired?", DECISION, 875, 200, 160, 90))
    cells.append(node("c-block", "Cannot vote\nSee status", END, 1070, 215, 110, 60))
    cells.append(node("c-list", "List active\nelections", PROCESS, 890, 320, 130, 60))
    cells.append(node("c-open", "Voting period\nopen?", DECISION, 875, 410, 160, 90))
    cells.append(node("c-wait", "View only /\nwait for window", END, 1070, 425, 110, 60))
    cells.append(node("c-voted", "Already voted\nor pending?", DECISION, 875, 530, 160, 90))
    cells.append(node("c-receipt", "View ballot\nreceipt / PDF", PROCESS_ORANGE, 1070, 545, 120, 60))
    cells.append(node("c-ballot", "Select candidates\nper position", PROCESS, 890, 650, 130, 60))
    cells.append(node("c-submit", "Submit ballot", PROCESS_ORANGE, 890, 740, 130, 60))
    cells.append(node("c-queue", "Queue ballot\nsubmission job", PROCESS, 890, 830, 130, 60))
    cells.append(node("c-process", "Process votes &\ncreate receipt", PROCESS_ORANGE, 880, 920, 150, 60))
    cells.append(node("c-done", "Ballot complete", END, 905, 1020, 100, 50))
    cells.append(node("c-results", "View results /\nlive standing", PROCESS, 890, 1120, 130, 60))

    cells.append(edge("ec1", "c-start", "c-can"))
    cells.append(edge("ec2", "c-can", "c-list", "Yes"))
    cells.append(edge("ec3", "c-can", "c-block", "No", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("ec4", "c-list", "c-open"))
    cells.append(edge("ec5", "c-open", "c-voted", "Yes"))
    cells.append(edge("ec6", "c-open", "c-wait", "No", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("ec7", "c-voted", "c-receipt", "Yes", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(edge("ec8", "c-voted", "c-ballot", "No"))
    cells.append(edge("ec9", "c-ballot", "c-submit"))
    cells.append(edge("ec10", "c-submit", "c-queue"))
    cells.append(edge("ec11", "c-queue", "c-process"))
    cells.append(edge("ec12", "c-process", "c-done"))
    cells.append(
        edge(
            "ec13",
            "c-done",
            "c-results",
            "Later",
            (0.5, 1),
            (0.5, 0),
            oy=-12,
        )
    )
    cells.append(
        edge(
            "ec14",
            "c-receipt",
            "c-results",
            "",
            (0.5, 1),
            (1, 0.5),
            [(1130, 640), (1130, 1150), (1020, 1150)],
            along=0.6,
            ox=10,
            oy=0,
        )
    )
    # Cross: dashboard → elections
    cells.append(
        edge(
            "ex2",
            "b-dash",
            "c-start",
            "Vote",
            (1, 0.5),
            (0, 0.5),
            [(660, 710), (660, 145), (870, 145)],
            along=0.4,
            ox=0,
            oy=-14,
        )
    )

    # ==================== 4. Committee ====================
    cells.append(node("d-start", "Committee /\nAdmin login", START, 1285, 120, 110, 55))
    cells.append(node("d-voters", "Open Voters\nlist", PROCESS, 1285, 210, 110, 55))
    cells.append(node("d-review", "Review OCR &\nchecklist score", PROCESS_GOLD, 1275, 295, 130, 60))
    cells.append(node("d-decide", "Approve\nvoter?", DECISION, 1260, 385, 160, 90))
    cells.append(node("d-verify", "Mark verified\n(verified_at)", PROCESS, 1285, 510, 110, 55))
    cells.append(node("d-reject", "Reject /\nrerun OCR", PROCESS_PURPLE, 1460, 400, 120, 60))
    cells.append(node("d-elect", "Manage elections\n& candidates", PROCESS, 1280, 600, 120, 60))
    cells.append(node("d-react", "Process reactivation\nrequests", PROCESS_PURPLE, 1270, 700, 140, 60))
    cells.append(node("d-support", "Approve / reply\nto support tickets", PROCESS_PURPLE, 1270, 800, 140, 60))
    cells.append(node("d-monitor", "Monitoring &\nreports", PROCESS, 1285, 900, 110, 55))
    cells.append(node("d-settings", "Settings\n(admin only)", PROCESS_GOLD, 1285, 990, 110, 55))
    cells.append(node("d-end", "Done", END, 1305, 1090, 80, 50))

    cells.append(edge("ed1", "d-start", "d-voters"))
    cells.append(edge("ed2", "d-voters", "d-review"))
    cells.append(edge("ed3", "d-review", "d-decide"))
    cells.append(edge("ed4", "d-decide", "d-verify", "Yes"))
    cells.append(edge("ed5", "d-decide", "d-reject", "No", (1, 0.5), (0, 0.5), oy=0, ox=8))
    cells.append(
        edge(
            "ed6",
            "d-reject",
            "d-review",
            "Retry",
            (0.5, 0),
            (1, 0.5),
            [(1520, 360), (1520, 325), (1405, 325)],
            along=0.5,
            ox=10,
            oy=0,
        )
    )
    cells.append(edge("ed7", "d-verify", "d-elect"))
    cells.append(edge("ed8", "d-elect", "d-react"))
    cells.append(edge("ed9", "d-react", "d-support"))
    cells.append(edge("ed10", "d-support", "d-monitor"))
    cells.append(edge("ed11", "d-monitor", "d-settings"))
    cells.append(edge("ed12", "d-settings", "d-end"))
    # Cross: pending → committee review
    cells.append(
        edge(
            "ex3",
            "a-pending",
            "d-voters",
            "Review queue",
            (1, 0.5),
            (0, 0.5),
            [(290, 1140), (1140, 1140), (1140, 238), (1285, 238)],
            along=0.35,
            ox=0,
            oy=-14,
        )
    )

    # Legend
    cells.append(node("leg-title", "Legend", TITLE.replace("20", "13"), 40, 1570, 100, 22))
    cells.append(node("leg1", "Start / End", START, 40, 1600, 100, 40))
    cells.append(node("leg2", "Process", PROCESS, 160, 1600, 110, 40))
    cells.append(node("leg3", "Decision", DECISION, 290, 1590, 100, 55))
    cells.append(node("leg4", "Pending /\nSettings", PROCESS_GOLD, 410, 1600, 110, 40))
    cells.append(node("leg5", "Support /\nReactivation", PROCESS_PURPLE, 540, 1600, 120, 40))
    cells.append(node("leg6", "Ballot /\nVoting", PROCESS_ORANGE, 680, 1600, 110, 40))

    root = "\n        ".join(cells)
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<mxfile host="app.diagrams.net" agent="SSCEVS" version="22.1.0" type="device">
  <diagram id="sscevs-flowchart" name="SSCEVS Flowchart">
    <mxGraphModel dx="0" dy="0" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1600" pageHeight="1700" background="#ffffff" math="0" shadow="0">
      <root>
        {root}
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>
"""


def main() -> None:
    out = Path(__file__).with_name("sscevs-flowchart.drawio")
    out.write_text(build(), encoding="utf-8")
    print(f"Wrote {out}")


if __name__ == "__main__":
    main()
