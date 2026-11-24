# SSCEVS User Flow Guide

## 1. System Overview

SSCEVS is the Student Council Election Voting System for school election management. It supports three main user roles:

- Voter
- Committee
- Admin

The flow is designed to protect election integrity, keep voter verification organized, and give staff and committee members only the permissions they need.

The system covers:

- Student registration and identity verification
- Email and OTP validation
- ID scanning and OCR verification
- Election setup and candidate management
- Voting and ballot receipts
- Live monitoring and reporting
- Support tickets and announcements
- Account reactivation and expiry handling

---

## 2. Role Summary

### Voter
A voter is a student who registers, verifies identity, and casts a ballot once eligible.

Typical voter flow:

1. Register account
2. Upload school ID
3. Verify email/OTP
4. Wait for OCR and fraud review
5. Get approved or sent for admin review
6. Log in and access dashboard
7. View election list and candidates
8. Vote during election window
9. Receive ballot receipt
10. View results or announcements

### Committee
A committee member is a trusted election support user with limited but important operational access.

Typical committee flow:

1. Admin creates committee account
2. Committee logs in
3. System grants access only to assigned pages
4. Committee can manage elections, candidates, announcements, monitoring, support, reports, and voter review according to permissions
5. Committee cannot access admin-only system settings

### Admin
An admin is the full system manager.

Typical admin flow:

1. Create/maintain admin or committee accounts
2. Configure school settings
3. Manage departments, courses, year levels, positions, and partylists
4. Review voter registrations
5. Approve or reject voter verification requests
6. Manage election lifecycle from draft to close
7. Monitor live voting and turnout
8. Review support tickets and reactivation requests
9. Export reports and manage system settings

---

## 3. End-to-End Voter Flow

### 3.1 Public Access and Status Check
Before login, a student can check registration status without account access.

Flow:

1. Student visits /check-status
2. Enters Voter ID
3. System looks up voter record
4. System shows:
   - Email verification status
   - ID/OCR status
   - Account verification status
   - Overall account status

This helps a student know whether their account is pending, approved, or rejected.

### 3.2 Registration Start
A student starts by visiting /register.

Required registration information usually includes:

- Full name
- Email address
- Student ID number
- Department
- Course
- Year level
- Password

The form is validated before registration proceeds.

### 3.3 School ID Scan
After filling the form, the student proceeds to /register/id-scan.

At this stage:

- Student uploads or captures a school ID image
- Image quality is checked
- System validates the uploaded ID
- A user account is created with a role of voter
- A voter ID is generated automatically
- The account is saved with registration details and image file metadata

The student is then taken to a success page and receives a verification email.

### 3.4 Email Verification and OTP
The system sends:

- Verification email
- One-time password (OTP)
- A verification link token

Student flow:

1. Open email
2. Click verification link or enter OTP on /register/verify-otp
3. System marks email as verified
4. The system queues OCR verification for the uploaded ID

If email is not verified, the student cannot fully proceed to active voting access.

### 3.5 OCR and Fraud Review
After email verification, OCR processing starts in the background.

System actions:

- Extract data from the uploaded ID image
- Compare extracted data with the student’s registration form
- Calculate fraud score based on mismatch and other risk indicators
- Update document status and verification status

Verification outcomes:

- Score 80 or above: auto-approved
- Score below 80: sent for manual admin review

### 3.6 Approval or Review
Once OCR is complete:

- The account may become verified automatically
- Or it may remain pending and appear in the admin/committee review queue

Admin actions on voter accounts may include:

- Verify
- Reject
- Re-run OCR
- Flag suspicious name match

Only approved voters can log in and access voting features.

### 3.7 Login Flow
Once the account is approved and verified, the voter can log in.

Login restrictions include:

- Unverified email blocks login
- Unapproved registration blocks login
- Expired or disabled accounts are prevented from voting

### 3.8 Dashboard and Election Access
After login, the voter reaches the dashboard.

The dashboard may show:

- Election status
- Active election cards
- Results and announcements
- Support access
- Account profile information

The voter then opens /elections to see the available election or election list.

### 3.9 Voting Process
During an active election:

1. Voter opens the election page
2. Sees candidate list by position
3. Selects their vote for each contest
4. Submits ballot
5. System records the vote
6. System creates a ballot receipt
7. Voter downloads or views the receipt as proof

Important rules:

- One ballot per election
- One vote per relevant position
- Voter must be verified and not expired

### 3.10 Ballot Receipt and Tracking
After voting:

- The system creates a ballot receipt record
- A receipt PDF can be generated and downloaded
- The voter can view submission status and history

This ensures election transparency and proof of participation.

### 3.11 Support, Help, and FAQ
A voter can also:

- Open support tickets
- Chat with help desk or support team
- Access FAQ or AI chat support
- Review announcements

These tools are useful after approval, when the voter is using the system normally.

### 3.12 Results and Announcements
After election close or as allowed by the system, voters can view:

- Results page
- My votes page
- Announcements
- Voting status history

### 3.13 Expired, Disabled, or Reactivation Flow
If a voter account expires or is disabled, the flow becomes:

1. Account is marked expired or disabled
2. User is redirected to the disabled account or reactivation flow
3. User enters voter ID or goes through a reactivation request
4. Admin or committee reviews the request
5. Account is reactivated if valid

This prevents inactive or invalid accounts from voting when they should not.

---

## 4. End-to-End Admin Flow

### 4.1 Admin Account Creation and Access
Admin accounts are created by the system or by a higher-level administrative process.

Admin email patterns follow the system rule:

- @sscevs.admin.com = admin role

Admins have access to all system management features unless restricted elsewhere.

### 4.2 Admin Dashboard
The admin dashboard is the main control area.

It usually provides:

- Total voter counts
- Verified vs pending voters
- Election overview
- Monitoring summary
- Live status indicators

### 4.3 Election Management
Admin manages the election lifecycle:

- Draft election
- Publish or schedule election
- Activate election
- Monitor election while open
- Close election
- Review results

Admin actions may include:

- Create election
- Edit election
- Remove election
- Configure voting windows and timing

### 4.4 Candidate Management
Admin may manage candidate entries such as:

- Candidate name
- Position
- Partylist
- Photo and platform details
- Department or category assignment

This is important for ensuring the ballot is complete and valid before voting begins.

### 4.5 Voter Review and Verification
Admin is responsible for handling voter approval workflow.

Admin can:

- View the voter list
- Inspect OCR and fraud score data
- Review identity details
- Approve a voter
- Reject an invalid registration
- Re-run OCR if needed
- Flag suspicious or duplicate name cases

This is the major quality-control step to protect the election.

### 4.6 Support Ticket Management
The admin or assigned support team can:

- View tickets
- Approve or reject ticket requests
- Respond in chat
- Close tickets when resolved

This keeps voter issues and technical help organized.

### 4.7 Reporting and Monitoring
Admin can access monitoring and reports for:

- Live turnout
- Election standings
- Ballot actions
- Voter registration summary
- Non-voters list
- Ballot receipts
- Exportable reports in PDF or Excel formats

This helps maintain transparency and decision-making.

### 4.8 Settings and Configuration
Admin has access to system configuration pages such as:

- Department list
- Course list
- Year levels
- Positions
- Partylists
- School year settings
- DTS registration settings
- UA management settings
- Location range settings
- Account management
- Permission control
- Audit logs
- Registration attempts

This is where the admin configures the election system itself.

### 4.9 Account and Permission Administration
Admin can create and manage:

- Admin accounts
- Committee accounts
- User permissions
- Access restrictions
- Disabled account processing
- Audit trails

This ensures role-based access is enforced and accountable.

### 4.10 Final Outcome of Admin Flow
The admin ensures the system is ready for:

- Voter verification
- Candidate and election setup
- Secure and fair voting
- Monitoring and reporting
- Final result publication

Without admin oversight, the election pipeline would not remain reliable.

---

## 5. End-to-End Committee Flow

### 5.1 Committee Account Creation
Committee accounts are usually created by the admin.

Committee email pattern:

- @sscevs.committee.com = committee role

These accounts are created with special permissions and role-based access control.

### 5.2 Committee Login and Dashboard
A committee member logs in and is redirected to the dashboard if allowed.

The committee does not have full admin privileges. Instead, they receive access only to pages they are assigned.

### 5.3 Default Committee Access
Each committee account is assigned page permissions. Some default pages may include:

- Dashboard
- Candidates

Other pages can be granted depending on the needs of the committee role.

### 5.4 Committee Page Catalog
Committee members may be allowed access to pages such as:

- Dashboard
- Candidates
- Elections
- Voters
- Announcements
- Monitoring
- Reports
- Support
- Reactivation Requests

If a committee member does not have access to a page, the system blocks or redirects them.

### 5.5 Committee Role in Candidate and Election Management
Committee members can usually work on:

- Creating and updating candidates
- Creating and editing elections
- Reviewing candidate information and ballot setup
- Maintaining announcement items for voters

This helps support election operations without giving full administrator power.

### 5.6 Committee Role in Voter Review
Committee can often review voters and verification information if permitted.

This includes:

- Viewing voter accounts
- Checking verification status
- Approving or rejecting accounts
- Rerunning OCR
- Reviewing duplicate or flagged names

This is a key operational support role during voter onboarding and validation.

### 5.7 Committee Role in Monitoring and Reports
Committee can monitor election progress when assigned permission.

This may include:

- Live voting activity
- Turnout status
- Election standings
- Exported reports
- Downloadable summaries and analytics

### 5.8 Committee Role in Support and Reactivation
Committee may also manage:

- Voter support tickets
- Ticket approvals and rejections
- Re-activation requests
- Help desk communication

These tasks ensure that voters receive timely support while the election is running.

### 5.9 Committee Restrictions
Committee members are restricted from entering admin-only parts of the system, such as:

- Full system settings
- Secure account administration
- Permission-wide admin controls
- Audit and system configuration pages that are not allowed by their assigned access

The page-check system prevents unauthorized pages from being accessed.

---

## 6. Full Role Relationship Flow

Below is the combined workflow showing how the three roles connect.

```text
Guest / Student
   |
   v
Register account -> Scan ID -> Verify Email -> OCR Review
   |
   v
Admin / Committee review voter status
   |
   +--> Approved -> Voter login -> Vote -> Receipt -> Results
   |
   +--> Pending -> Admin review -> Approved or Rejected
   |
   +--> Rejected -> Fix or reactivation request

Admin
   |
   v
Configure system -> Manage elections -> Manage candidates -> Review voters
   |
   +--> Monitor voting -> Reports -> Support -> Settings

Committee
   |
   v
Assigned permissions -> Manage selected election pages
   |
   +--> Candidates / Elections / Voters / Monitoring / Reports / Support / Announcements
   |
   +--> Cannot access admin-only settings and system configuration
```

---

## 7. Common Business Rules

Across all roles, the system follows these important rules:

- Admin role has broad access
- Committee role has limited access based on page permissions
- Voter role is restricted to registration, voting, support, announcements, and profile actions
- Login is blocked for unverified or unapproved voters
- Election actions obey status and timing rules
- Ballot receipt creation is required for voting proof
- Account expiry and reactivation are enforced to protect validity
- Verification, support, and monitoring are all connected to the election integrity process

---

## 8. Practical Summary

### Voter Journey
Register -> Verify Email -> Upload ID -> OCR -> Approved or Review -> Login -> Vote -> Receipt -> Results

### Committee Journey
Login -> Assigned page permissions -> Manage elected tasks -> Support monitoring and reviews -> Avoid admin-only areas

### Admin Journey
Configure system -> Manage election lifecycle -> Review voters -> Monitor voting -> Generate reports -> Maintain settings and accounts

---

## 9. Final Note

This flow is complete because it covers the entire lifecycle from registration through approval, voting, monitoring, results, and support. The system is structured so that voters can participate safely, committee members support operations within their permissions, and admins control the full election infrastructure.
