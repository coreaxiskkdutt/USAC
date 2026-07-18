from app import app, db, User

ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "admin123"
ADMIN_EMAIL = "admin@usac.local"
ADMIN_FULL_NAME = "System Administrator"

with app.app_context():
    db.create_all()
    existing = User.query.filter_by(username=ADMIN_USERNAME).first()
    if existing:
        print(f"Admin user '{ADMIN_USERNAME}' already exists. Skipping.")
    else:
        admin = User(
            full_name=ADMIN_FULL_NAME,
            username=ADMIN_USERNAME,
            email=ADMIN_EMAIL,
            designation="Director",
            department="HSE",
            role="admin",
            is_approved=True,
        )
        admin.set_password(ADMIN_PASSWORD)
        db.session.add(admin)
        db.session.commit()
        print(f"Admin user created successfully!")
        print(f"  Username : {ADMIN_USERNAME}")
        print(f"  Password : {ADMIN_PASSWORD}")
        print(f"  Email    : {ADMIN_EMAIL}")
