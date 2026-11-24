# SSCEVS Module-by-Module Flow Guide

## 1. Homepage / Public Module

The homepage is the first entry point for everyone.

### What happens here
- Users see the system overview and the election purpose.
- Guests can access About, Guide, Live Standing, and public election information.
- Students can go to login or register.
- Visitors can check registration status without being logged in.
- This module helps users understand what the system does before they start using it.

## 2. Login and Registration Module

### Login flow
1. User enters email and password.
2. The system validates the account.
3. The system checks the user role: voter, committee, or admin.
4. If the account is expired, disabled, or unverified, access is restricted.

### Registration flow
1. Student opens the registration page.
2. Student fills in their personal data.
3. The student uploads a school ID image.
4. System validates the information and creates a voter account.
5. Verification email is sent.
6. The user uses OTP or a verification link.
7. OCR processing begins.
8. Admin checks the result if needed.
9. Approved users can login and vote.

## 3. Voter Module

This is the main flow for student users.

### Voter journey
1. User logs into the system.
2. User sees the dashboard.
3. User views election information and candidate lists.
4. User opens the election page.
5. User selects choices for each position.
6. User submits the ballot.
7. The system records the vote.
8. Ballot receipt is generated.
9. User can view results, announcements, and support information.

## 4. Voter Verification Flow

This is a detailed account flow that happens before the voter can vote.

1. Student registers.
2. Student uploads school ID.
3. A voter ID is generated.
4. Verification email is sent.
5. Email is verified using OTP or verification link.
6. OCR reads the ID image.
7. OCR data is compared with registration data.
8. Fraud score is calculated.
9. If the score is high, the account is auto-approved.
10. If the score is low, the account goes to admin review.
11. After approval, the voter can log in to the voting system.

## 5. Voting Module

### Voting process
1. The voter opens the election page.
2. The voter sees each position and the candidate options.
3. The voter chooses a candidate for each relevant position.
4. The ballot is submitted.
5. The system validates the election status and voter status.
6. The vote is saved.
7. A ballot receipt is created.
8. The voter can view or download the proof of vote.

### Important rules
- One ballot per election.
- One vote per position.
- Voters must be verified and not expired.
- Voting is only allowed during the active election window.

## 6. Results, Announcements, and Support Module

After or during the election, the voter can access:
- Results page
- My votes page
- Announcements
- Support tickets
- FAQ / AI help
- Help desk messages

This module keeps communication clear and organized for students.

## 7. Committee Module

The committee acts as a support and operations group with limited access.

### Typical committee flow
1. Admin creates the committee account.
2. Committee member logs in.
3. System grants access only to assigned pages.
4. Committee can manage allowed functions such as:
   - Candidates
   - Elections
   - Announcements
   - Monitoring
   - Reports
   - Support tickets
   - Voter review if permitted
5. Committee cannot access restricted admin settings.

### Committee access principle
Committee access is permission-based. They do not have full admin rights unless the system explicitly allows it.

## 8. Admin Module

The admin module is the main control center of the system. It is the highest-level management area and follows the actual side menu order of the platform.

### 8.1 Dashboard
This is the first module the admin sees after login.

### What happens here
- Admin sees the system summary.
- Admin reviews election status, voter totals, and verification progress.
- Admin checks whether the system is ready for registration, voting, or results.

### Purpose
The dashboard gives the admin the overall picture before going into detailed administration.

### 8.2 Elections
This module manages the election lifecycle.

### Flow
1. Admin creates an election.
2. Admin sets election details and schedule.
3. Admin publishes or schedules the election.
4. The election moves from draft to scheduled to active.
5. Admin monitors it while it is running.
6. Admin closes the election after the voting period ends.

### Purpose
This controls when and how the election happens.

### 8.3 Candidates
This module manages the candidate list for each election.

### Flow
1. Admin opens the candidates page.
2. Admin adds the candidate name and position.
3. Admin assigns partylist or group affiliation if needed.
4. Admin adds platform detail and photo if required.
5. Admin updates or removes candidate records as needed.

### Purpose
This ensures the ballot is complete and the election has valid candidates.

### 8.4 Voters
This is the voter management and verification section.

### Flow
1. Admin opens the voters page.
2. Admin checks each voter’s profile and status.
3. Admin reviews OCR result and fraud score.
4. Admin verifies, rejects, or re-runs OCR for each voter.
5. Admin checks duplicate or suspicious name cases.

### Purpose
This module decides which students are allowed to participate in voting.

### 8.5 Disabled Accounts
This module handles voter accounts that have been disabled.

### Flow
1. Admin opens the disabled accounts page.
2. Admin reviews which accounts were disabled and why.
3. Admin checks whether the account should be reactivated or permanently removed.
4. Admin processes the disabled account based on the current policy.

### Purpose
This section keeps account control organized and prevents invalid accounts from continuing.

### 8.6 Reactivation Request
This module handles accounts that need to return to active status.

### Flow
1. A voter requests reactivation.
2. Admin views the request.
3. Admin checks the voter’s details and reason for reactivation.
4. Admin approves or rejects the request.
5. The account is restored if valid.

### Purpose
This allows valid students to regain access while still protecting system integrity.

### 8.7 Support
This handles help and support requests from voters.

### Flow
1. A voter submits a support ticket.
2. Admin reviews the request.
3. Admin approves or rejects the ticket.
4. Admin responds and resolves the issue.
5. The ticket is closed after resolution.

### Purpose
This keeps communication and problem-solving organized for users.

### 8.8 Announcements
This module manages public communication for the election.

### Flow
1. Admin opens the announcements page.
2. Admin creates or edits announcements.
3. Admin chooses the message content and date visibility.
4. Admin publishes the announcement to users.

### Purpose
Announcements keep voters informed about election updates, reminders, and important announcements.

### 8.9 Monitoring
This is the live election monitoring module.

### Flow
1. Admin opens the monitoring page.
2. Admin checks live turnout and participation.
3. Admin reviews election standing and vote activity.
4. Admin responds to unusual or risky issues during the vote.

### Purpose
This keeps the election transparent and under control while it is active.

### 8.10 Reports
This module collects election and voter data for official review.

### Flow
1. Admin opens the reports page.
2. Admin chooses the category to review.
3. Admin checks turnout, voter registration, non-voters, or other summaries.
4. Admin exports the report if needed.

### Purpose
This creates proof and documentation of the election process.

### 8.11 Accounts
This module manages administrator and committee user accounts.

### Flow
1. Admin creates or updates an account.
2. Admin assigns the correct role.
3. Admin updates access settings or account status.
4. Admin removes or modifies accounts when necessary.

### Purpose
This section ensures the proper people have correct access and responsibilities.

### 8.12 Permissions
This module controls what each committee user is allowed to access.

### Flow
1. Admin opens the permissions page.
2. Admin selects a user.
3. Admin assigns or removes access to specific pages.
4. Admin saves the permission settings.

### Purpose
This limits committee access to only the pages they are allowed to manage.

### 8.13 Settings
This is the system configuration page.

### Flow
1. Admin opens settings.
2. Admin updates department, course, year level, position, and partylist data.
3. Admin configures school-year, location, and other system rules.
4. Admin saves the settings for the entire platform.

### Purpose
This keeps the election system structured and correct.

### 8.14 System
This is the final technical admin module.

### Flow
1. Admin opens the system page.
2. Admin checks system status and important technical information.
3. Admin reviews operational or technical health indicators.
4. Admin performs final system management tasks if needed.

### Purpose
This module handles the backend health and overall platform management.

### 8.15 Admin Flow in Correct Order
The admin modules should be followed in this order:

Dashboard -> Elections -> Candidates -> Voters -> Disabled Accounts -> Reactivation Request -> Support -> Announcements -> Monitoring -> Reports -> Accounts -> Permissions -> Settings -> System

## 9. Full Combined Flow

Homepage -> Login/Register -> Email Verification -> ID Review -> Approval -> Dashboard -> Election -> Vote -> Receipt -> Results -> Reports -> Support

### Role-wise summary
- Voter: register -> verify -> vote -> receipt -> results
- Committee: login -> assigned pages -> support election tasks
- Admin: Dashboard -> Elections -> Candidates -> Voters -> Disabled Accounts -> Reactivation Request -> Support -> Announcements -> Monitoring -> Reports -> Accounts -> Permissions -> Settings -> System

## 10. Important Rules Across the System

- Admin has the broadest administrative access.
- Committee has limited access based on permissions.
- Voters must be verified before they can vote.
- Voting is restricted by election status and time window.
- Support, announcements, and results are part of the user journey.
- Ballot receipts provide an official record of voting activity.

## 11. Final Summary

The full SSCEVS flow starts at the homepage, continues through login or registration, moves into voter verification, then enters the voting and results modules. The committee supports the election with limited permissions, while the admin manages the system, election process, voter approval, monitoring, and reporting.

This is the complete and understandable flow of the system from beginning to end.
