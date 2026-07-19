from app import app, db, User, Report
from datetime import datetime, date, timedelta
import random

with app.app_context():
    db.create_all()

    # --- USERS ---
    users_data = [
        # Admin
        {"full_name": "System Administrator", "username": "admin", "email": "admin@usac.local",
         "designation": "Director", "department": "HSE", "role": "admin", "is_approved": True, "password": "admin123"},

        # Safety Officers
        {"full_name": "Maria Santos", "username": "msantos", "email": "msantos@usac.local",
         "designation": "Manager", "department": "HSE", "role": "safety_officer", "is_approved": True, "password": "pass123"},
        {"full_name": "Rafael Cruz", "username": "rcruz", "email": "rcruz@usac.local",
         "designation": "Superintendent", "department": "HSE", "role": "safety_officer", "is_approved": True, "password": "pass123"},

        # Employees (approved)
        {"full_name": "Juan Dela Cruz", "username": "jdelacruz", "email": "jdelacruz@usac.local",
         "designation": "Operator", "department": "Operations", "role": "employee", "is_approved": True, "password": "pass123"},
        {"full_name": "Ana Reyes", "username": "areyes", "email": "areyes@usac.local",
         "designation": "Technician", "department": "Maintenance", "role": "employee", "is_approved": True, "password": "pass123"},
        {"full_name": "Carlos Garcia", "username": "cgarcia", "email": "cgarcia@usac.local",
         "designation": "Engineer", "department": "Engineering", "role": "employee", "is_approved": True, "password": "pass123"},
        {"full_name": "Elena Mendoza", "username": "emendoza", "email": "emendoza@usac.local",
         "designation": "Supervisor", "department": "Operations", "role": "employee", "is_approved": True, "password": "pass123"},
        {"full_name": "Roberto Lim", "username": "rlim", "email": "rlim@usac.local",
         "designation": "Foreman", "department": "Maintenance", "role": "employee", "is_approved": True, "password": "pass123"},
        {"full_name": "Sofia Tan", "username": "stan", "email": "stan@usac.local",
         "designation": "Inspector", "department": "Quality", "role": "employee", "is_approved": True, "password": "pass123"},
        {"full_name": "Miguel Bautista", "username": "mbautista", "email": "mbautista@usac.local",
         "designation": "Coordinator", "department": "Logistics", "role": "employee", "is_approved": True, "password": "pass123"},

        # Employees (pending)
        {"full_name": "Patricia Aquino", "username": "paquino", "email": "paquino@usac.local",
         "designation": "Operator", "department": "Operations", "role": "employee", "is_approved": False, "password": "pass123"},
        {"full_name": "Daniel Ramos", "username": "dramos", "email": "dramos@usac.local",
         "designation": "Technician", "department": "IT", "role": "employee", "is_approved": False, "password": "pass123"},
    ]

    created_users = {}
    for u in users_data:
        existing = User.query.filter_by(username=u["username"]).first()
        if existing:
            created_users[u["username"]] = existing
            continue
        user = User(
            full_name=u["full_name"], username=u["username"], email=u["email"],
            designation=u["designation"], department=u["department"],
            role=u["role"], is_approved=u["is_approved"],
        )
        user.set_password(u["password"])
        db.session.add(user)
        db.session.flush()
        created_users[u["username"]] = user

    db.session.commit()
    print(f"Users: {len(created_users)} total ({sum(1 for u in created_users.values() if u.role=='admin')} admin, "
          f"{sum(1 for u in created_users.values() if u.role=='safety_officer')} SO, "
          f"{sum(1 for u in created_users.values() if u.role=='employee' and u.is_approved)} approved emp, "
          f"{sum(1 for u in created_users.values() if u.role=='employee' and not u.is_approved)} pending)")

    # --- REPORTS ---
    if Report.query.count() > 0:
        print("Reports already exist. Skipping.")
    else:
        reports_data = [
            # Unsafe Acts
            {"type": "Unsafe Act", "title": "Forklift operated without seatbelt",
             "desc": "Operator observed driving loaded forklift through main aisle without wearing the seatbelt. Risk of ejection during sudden stop or collision.",
             "category": "PPE Violation", "severity": "High", "status": "Open",
             "department": "Operations", "location": "Warehouse A, Aisle 6", "reporter": "Ana Reyes", "assignee": "Roberto Lim", "days_ago": 2},

            {"type": "Unsafe Act", "title": "Hot work performed without permit",
             "desc": "Contractor observed welding near flammable storage area without a visible hot work permit or fire watch in place.",
             "category": "Working Without Authorization", "severity": "Critical", "status": "Under Investigation",
             "department": "Maintenance", "location": "Yard 3, Tank Farm perimeter", "reporter": "Carlos Garcia", "assignee": "Maria Santos", "days_ago": 5},

            {"type": "Unsafe Act", "title": "Bypassing machine guard on press",
             "desc": "Operator removed interlock guard to clear a jam without following lockout/tagout procedure. Machine could cycle unexpectedly.",
             "category": "Bypassing Safety Devices", "severity": "Critical", "status": "Open",
             "department": "Operations", "location": "Production Line 2, Press Station", "reporter": "Elena Mendoza", "assignee": "Rafael Cruz", "days_ago": 1},

            {"type": "Unsafe Act", "title": "Climbing racking instead of using ladder",
             "desc": "Warehouse worker climbed storage racking 3 meters high to retrieve a pallet instead of using the available order picker.",
             "category": "Unsafe Positioning", "severity": "Medium", "status": "Corrective Action In Progress",
             "department": "Logistics", "location": "Warehouse B, Zone C", "reporter": "Miguel Bautista", "assignee": "Roberto Lim", "days_ago": 8},

            {"type": "Unsafe Act", "title": "Entering confined space without gas test",
             "desc": "Maintenance crew began work inside a vessel without performing atmospheric testing or having a standby person stationed at the entry point.",
             "category": "Failure to Lock Out/Tag Out", "severity": "Critical", "status": "Under Investigation",
             "department": "Maintenance", "location": "Plant 1, Vessel V-204", "reporter": "Roberto Lim", "assignee": "Maria Santos", "days_ago": 3},

            {"type": "Unsafe Act", "title": "Working at height without harness",
             "desc": "Electrician working on overhead cable tray at 5m height without fall protection. No anchor points established.",
             "category": "PPE Violation", "severity": "High", "status": "Open",
             "department": "Engineering", "location": "Admin Building, Ceiling void Floor 3", "reporter": "Carlos Garcia", "assignee": "Rafael Cruz", "days_ago": 1},

            {"type": "Unsafe Act", "title": "Horseplay in loading dock area",
             "desc": "Two workers engaged in pushing match near active forklift traffic. Risk of being struck by moving equipment.",
             "category": "Horseplay", "severity": "Medium", "status": "Closed",
             "department": "Operations", "location": "Loading Dock 2", "reporter": "Juan Dela Cruz", "assignee": "Elena Mendoza", "days_ago": 14,
             "root_cause": "Lack of supervision during break period. Workers unaware of loading dock traffic risks.",
             "corrective_action": "Verbal warning issued to both workers. Refresher safety briefing conducted for all shift workers. Increased supervisor presence during breaks."},

            {"type": "Unsafe Act", "title": "Improper manual lifting of 30kg drum",
             "desc": "Worker attempted to lift a 30kg chemical drum alone without mechanical aid or assistance, risking back injury.",
             "category": "Improper Lifting", "severity": "Medium", "status": "Closed",
             "department": "Operations", "location": "Chemical Storage Area", "reporter": "Sofia Tan", "assignee": "Roberto Lim", "days_ago": 20,
             "root_cause": "Drum trolley was out of service. Worker chose to lift manually rather than wait for replacement.",
             "corrective_action": "Replacement drum trolley procured. Training on manual handling limits reinforced. procedure updated to mandate mechanical aids for loads over 15kg."},

            # Unsafe Conditions
            {"type": "Unsafe Condition", "title": "Exposed wiring near wash station",
             "desc": "Junction box cover missing, live wiring exposed at floor level next to a wet-process area. Risk of electrocution.",
             "category": "Electrical Hazard", "severity": "Critical", "status": "Open",
             "department": "Maintenance", "location": "Plant 2, Wash Bay", "reporter": "Ana Reyes", "assignee": "Carlos Garcia", "days_ago": 1},

            {"type": "Unsafe Condition", "title": "Spill kit signage faded and unreadable",
             "desc": "Signage pointing to spill kit location has faded to the point of being unreadable. New personnel cannot locate spill response equipment.",
             "category": "Missing Signage", "severity": "Low", "status": "Closed",
             "department": "HSE", "location": "Mixing Room 2", "reporter": "Sofia Tan", "assignee": "Maria Santos", "days_ago": 25,
             "root_cause": "Signage material not rated for chemical exposure. No periodic inspection schedule in place.",
             "corrective_action": "Chemical-resistant signage installed. Quarterly signage inspection added to HSE checklist."},

            {"type": "Unsafe Condition", "title": "Wet floor in main corridor without barriers",
             "desc": "Water leaking from overhead pipe creating a continuous wet surface in the main pedestrian corridor. No cones, signs, or barriers placed.",
             "category": "Wet/Slippery Surfaces", "severity": "High", "status": "In Progress",
             "department": "Operations", "location": "Building A, Ground Floor Corridor", "reporter": "Elena Mendoza", "assignee": "Roberto Lim", "days_ago": 4},

            {"type": "Unsafe Condition", "title": "Emergency exit blocked by stored materials",
             "desc": "Pallets and boxed inventory stacked in front of Fire Exit 4, preventing the door from opening fully. Violation of fire code.",
             "category": "Blocked Emergency Exit", "severity": "Critical", "status": "Open",
             "department": "Logistics", "location": "Warehouse A, Fire Exit 4", "reporter": "Miguel Bautista", "assignee": "Maria Santos", "days_ago": 1},

            {"type": "Unsafe Condition", "title": "Inadequate lighting in stairwell B",
             "desc": "Two of six light fixtures in Stairwell B are non-functional. Illumination level below minimum required for safe egress.",
             "category": "Poor Lighting", "severity": "Medium", "status": "Corrective Action In Progress",
             "department": "Operations", "location": "Building B, Stairwell B", "reporter": "Juan Dela Cruz", "assignee": "Carlos Garcia", "days_ago": 6},

            {"type": "Unsafe Condition", "title": "Defective overhead crane hook",
             "desc": "Safety latch on overhead crane hook in Bay 5 is broken. Hook could slip off slings during lifting operations.",
             "category": "Defective Equipment", "severity": "High", "status": "Open",
             "department": "Maintenance", "location": "Workshop, Bay 5 Crane", "reporter": "Roberto Lim", "assignee": "Rafael Cruz", "days_ago": 2},

            {"type": "Unsafe Condition", "title": "Missing guardrail on mezzanine edge",
             "desc": "Section of guardrail (approx 3m) removed for maintenance access and not reinstated. Fall hazard to workers below and above.",
             "category": "Fall Hazard", "severity": "High", "status": "Under Investigation",
             "department": "Engineering", "location": "Production Line 1, Mezzanine", "reporter": "Carlos Garcia", "assignee": "Maria Santos", "days_ago": 3},

            {"type": "Unsafe Condition", "title": "Chemical drum stored near ignition source",
             "desc": "Flammable solvent drum stored within 2 meters of an electrical panel that produces sparks during switching operations.",
             "category": "Fire/Explosion Risk", "severity": "Critical", "status": "Open",
             "department": "Operations", "location": "Chemical Store, Bay 3", "reporter": "Sofia Tan", "assignee": "Rafael Cruz", "days_ago": 1},

            {"type": "Unsafe Condition", "title": "Noise levels exceeding PEL in grinding area",
             "desc": "Sound level measurement recorded 98 dBA TWA in grinding area. OSHA PEL is 90 dBA. Engineering controls not in place.",
             "category": "Noise Exposure", "severity": "High", "status": "In Progress",
             "department": "Engineering", "location": "Workshop, Grinding Bay", "reporter": "Ana Reyes", "assignee": "Carlos Garcia", "days_ago": 7},

            {"type": "Unsafe Condition", "title": "Poor ventilation in paint booth",
             "desc": "Exhaust fan running at reduced speed. VOC readings above acceptable limits. Workers experiencing headaches and eye irritation.",
             "category": "Poor Ventilation", "severity": "High", "status": "Open",
             "department": "Maintenance", "location": "Paint Shop, Booth 2", "reporter": "Elena Mendoza", "assignee": "Roberto Lim", "days_ago": 2},

            {"type": "Unsafe Condition", "title": "Damaged scaffolding planks",
             "desc": "Several scaffold planks in supported scaffold are cracked or split. Load capacity compromised. Workers currently using scaffold.",
             "category": "Structural Deficiency", "severity": "Critical", "status": "Closed",
             "department": "Engineering", "location": "Plant 3, Exterior Wall", "reporter": "Carlos Garcia", "assignee": "Maria Santos", "days_ago": 18,
             "root_cause": "Planks not inspected before reuse. No scaffold inspection checklist in use.",
             "corrective_action": "All damaged planks replaced. Scaffold inspection protocol implemented. Weekly inspection by competent person mandated."},
        ]

        for i, r in enumerate(reports_data):
            created = datetime.utcnow() - timedelta(days=r["days_ago"])
            report = Report(
                report_number=f"USAC-2026-{str(i+1).zfill(4)}",
                report_type=r["type"],
                title=r["title"],
                description=r["desc"],
                category=r["category"],
                severity=r["severity"],
                status=r["status"],
                department=r["department"],
                location=r["location"],
                date_observed=(date.today() - timedelta(days=r["days_ago"])),
                reported_by=r["reporter"],
                assigned_to=r["assignee"],
                reassignment_count=random.randint(0, 3),
                root_cause=r.get("root_cause"),
                corrective_action=r.get("corrective_action"),
                user_id=created_users[[k for k, v in created_users.items() if v.full_name == r["reporter"]][0]].id if [k for k, v in created_users.items() if v.full_name == r["reporter"]] else None,
                created_at=created,
                updated_at=created + timedelta(hours=random.randint(1, 48)) if r["status"] != "Open" else created,
            )
            db.session.add(report)

        db.session.commit()
        print(f"Reports: {Report.query.count()} created")

    print("\n--- LOGIN CREDENTIALS ---")
    print("Admin:          admin / admin123")
    print("Safety Officer: msantos / pass123")
    print("Safety Officer: rcruz / pass123")
    print("Employee:       jdelacruz / pass123")
    print("Employee:       areyes / pass123")
    print("Employee:       cgarcia / pass123")
    print("Employee:       emendoza / pass123")
    print("Employee:       rlim / pass123")
    print("Employee:       stan / pass123")
    print("Employee:       mbautista / pass123")
    print("Pending:        paquino / pass123")
    print("Pending:        dramos / pass123")
