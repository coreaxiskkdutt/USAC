import os
import uuid
from datetime import datetime, date
from functools import wraps

from flask import (
    Flask, render_template, request, redirect, url_for,
    flash, jsonify, send_from_directory, session
)
from flask_sqlalchemy import SQLAlchemy
from flask_wtf import FlaskForm
from flask_wtf.file import FileField, FileAllowed
from wtforms import (
    StringField, TextAreaField, SelectField, DateField,
    SubmitField, IntegerField, HiddenField, PasswordField
)
from wtforms.validators import DataRequired, Optional, Length, Email, EqualTo, ValidationError
from werkzeug.security import generate_password_hash, check_password_hash

app = Flask(__name__)
app.config["SECRET_KEY"] = os.environ.get("SECRET_KEY", "usac-secret-key-change-in-production")
app.config["SQLALCHEMY_DATABASE_URI"] = "sqlite:///usac.db"
app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False
app.config["UPLOAD_FOLDER"] = os.path.join(os.path.dirname(os.path.abspath(__file__)), "uploads")
app.config["MAX_CONTENT_LENGTH"] = 16 * 1024 * 1024

db = SQLAlchemy(app)

ALLOWED_EXTENSIONS = {"png", "jpg", "jpeg", "gif", "pdf"}

DEPARTMENTS = [
    "Operations", "Maintenance", "Engineering", "HSE", "Human Resources",
    "Procurement", "Logistics", "Quality", "IT", "Finance", "Admin", "Other"
]

CATEGORIES_ACT = [
    "PPE Violation", "Unsafe Positioning", "Improper Lifting", "Horseplay",
    "Bypassing Safety Devices", "Improper Use of Tools", "Failure to Lock Out/Tag Out",
    "Working Without Authorization", "Ignoring Warning Signs", "Improper Housekeeping",
    "Fatigue/Impairment", "Other"
]

CATEGORIES_CONDITION = [
    "Unguarded Machinery", "Defective Equipment", "Wet/Slippery Surfaces",
    "Poor Lighting", "Chemical Exposure", "Electrical Hazard", "Fall Hazard",
    "Confined Space", "Noise Exposure", "Fire/Explosion Risk",
    "Poor Ventilation", "Missing Signage", "Structural Deficiency",
    "Blocked Emergency Exit", "Improper Storage", "Other"
]

SEVERITY_LEVELS = ["Low", "Medium", "High", "Critical"]
REPORT_TYPES = ["Unsafe Act", "Unsafe Condition"]
STATUS_OPTIONS = ["Open", "Under Investigation", "Corrective Action In Progress", "Closed"]
ROLES = {"employee": "Employee", "safety_officer": "Safety Officer", "admin": "Admin"}


DESIGNATIONS = [
    "Operator", "Technician", "Engineer", "Supervisor", "Foreman",
    "Manager", "Coordinator", "Specialist", "Inspector", "Planner",
    "Officer", "Analyst", "Director", "Superintendent", "Lead", "Other"
]


class User(db.Model):
    __tablename__ = "users"
    id = db.Column(db.Integer, primary_key=True)
    full_name = db.Column(db.String(150), nullable=False)
    username = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(256), nullable=False)
    role = db.Column(db.String(20), nullable=False, default="employee")
    designation = db.Column(db.String(80), nullable=False)
    department = db.Column(db.String(50), nullable=False)
    is_approved = db.Column(db.Boolean, nullable=False, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    reports = db.relationship("Report", backref="author", lazy=True)

    def set_password(self, password):
        self.password_hash = generate_password_hash(password)

    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

    @property
    def is_safety_officer(self):
        return self.role == "safety_officer"

    @property
    def is_admin(self):
        return self.role == "admin"

    @property
    def can_manage_users(self):
        return self.role in ("admin", "safety_officer")

    @property
    def can_edit_reports(self):
        return self.role in ("admin", "safety_officer")

    def __repr__(self):
        return f"<User {self.username}>"


class Report(db.Model):
    __tablename__ = "reports"
    id = db.Column(db.Integer, primary_key=True)
    report_number = db.Column(db.String(20), unique=True, nullable=False)
    report_type = db.Column(db.String(20), nullable=False)
    title = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text, nullable=False)
    category = db.Column(db.String(100), nullable=False)
    severity = db.Column(db.String(20), nullable=False)
    status = db.Column(db.String(30), nullable=False, default="Open")
    department = db.Column(db.String(50), nullable=False)
    location = db.Column(db.String(200), nullable=False)
    date_observed = db.Column(db.Date, nullable=False)
    reported_by = db.Column(db.String(100), nullable=False)
    contact_email = db.Column(db.String(120), nullable=True)
    immediate_action = db.Column(db.Text, nullable=True)
    root_cause = db.Column(db.Text, nullable=True)
    corrective_action = db.Column(db.Text, nullable=True)
    assigned_to = db.Column(db.String(100), nullable=True)
    reassignment_count = db.Column(db.Integer, nullable=False, default=0)
    photo_filename = db.Column(db.String(200), nullable=True)
    user_id = db.Column(db.Integer, db.ForeignKey("users.id"), nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    def __repr__(self):
        return f"<Report {self.report_number}>"


def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if "user_id" not in session:
            flash("Please log in to access this page.", "warning")
            return redirect(url_for("login"))
        return f(*args, **kwargs)
    return decorated


def safety_officer_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if "user_id" not in session:
            flash("Please log in to access this page.", "warning")
            return redirect(url_for("login"))
        user = db.session.get(User, session["user_id"])
        if not user or not user.can_manage_users:
            flash("Access denied. Safety Officer or Admin role required.", "danger")
            return redirect(url_for("dashboard"))
        return f(*args, **kwargs)
    return decorated


def admin_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if "user_id" not in session:
            flash("Please log in to access this page.", "warning")
            return redirect(url_for("login"))
        user = db.session.get(User, session["user_id"])
        if not user or not user.is_admin:
            flash("Access denied. Admin role required.", "danger")
            return redirect(url_for("dashboard"))
        return f(*args, **kwargs)
    return decorated


def get_current_user():
    if "user_id" in session:
        return db.session.get(User, session["user_id"])
    return None


class LoginForm(FlaskForm):
    username = StringField("Username", validators=[DataRequired(), Length(max=80)])
    password = PasswordField("Password", validators=[DataRequired()])
    submit = SubmitField("Log In")


class RegisterForm(FlaskForm):
    full_name = StringField("Full Name", validators=[DataRequired(), Length(max=150)])
    username = StringField("Username", validators=[DataRequired(), Length(min=3, max=80)])
    email = StringField("Email", validators=[DataRequired(), Email(), Length(max=120)])
    designation = SelectField("Designation", choices=[(d, d) for d in DESIGNATIONS], validators=[DataRequired()])
    department = SelectField("Department", choices=[(d, d) for d in DEPARTMENTS], validators=[DataRequired()])
    password = PasswordField("Password", validators=[DataRequired(), Length(min=6)])
    password_confirm = PasswordField("Confirm Password", validators=[DataRequired(), EqualTo("password", message="Passwords must match")])
    submit = SubmitField("Submit for Approval")

    def validate_username(self, field):
        if User.query.filter_by(username=field.data).first():
            raise ValidationError("Username already taken.")

    def validate_email(self, field):
        if User.query.filter_by(email=field.data).first():
            raise ValidationError("Email already registered.")


class ReportForm(FlaskForm):
    report_type = SelectField("Report Type", choices=[(t, t) for t in REPORT_TYPES], validators=[DataRequired()])
    title = StringField("Title", validators=[DataRequired(), Length(max=200)])
    description = TextAreaField("Description of Unsafe Act/Condition", validators=[DataRequired(), Length(max=2000)])
    category = SelectField("Category", choices=[], validators=[DataRequired()])
    severity = SelectField("Severity Level", choices=[(s, s) for s in SEVERITY_LEVELS], validators=[DataRequired()])
    department = SelectField("Department", choices=[(d, d) for d in DEPARTMENTS], validators=[DataRequired()])
    location = StringField("Location", validators=[DataRequired(), Length(max=200)])
    date_observed = DateField("Date Observed", format="%Y-%m-%d", validators=[DataRequired()], default=date.today)
    reported_by = StringField("Reported By", validators=[DataRequired(), Length(max=100)])
    contact_email = StringField("Contact Email", validators=[Optional(), Email(), Length(max=120)])
    immediate_action = TextAreaField("Immediate Action Taken", validators=[Optional(), Length(max=1000)])
    photo = FileField("Photo Evidence", validators=[Optional(), FileAllowed(ALLOWED_EXTENSIONS, "Images and PDFs only!")])
    submit = SubmitField("Submit Report")


class EditReportForm(FlaskForm):
    status = SelectField("Status", choices=[(s, s) for s in STATUS_OPTIONS], validators=[DataRequired()])
    severity = SelectField("Severity Level", choices=[(s, s) for s in SEVERITY_LEVELS], validators=[DataRequired()])
    root_cause = TextAreaField("Root Cause Analysis", validators=[Optional(), Length(max=1000)])
    corrective_action = TextAreaField("Corrective Action", validators=[Optional(), Length(max=1000)])
    assigned_to = StringField("Assigned To", validators=[Optional(), Length(max=100)])
    submit = SubmitField("Update Report")


class FilterForm(FlaskForm):
    report_type = SelectField("Type", choices=[("", "All Types")] + [(t, t) for t in REPORT_TYPES], validators=[Optional()])
    severity = SelectField("Severity", choices=[("", "All Severities")] + [(s, s) for s in SEVERITY_LEVELS], validators=[Optional()])
    status = SelectField("Status", choices=[("", "All Statuses")] + [(s, s) for s in STATUS_OPTIONS], validators=[Optional()])
    department = SelectField("Department", choices=[("", "All Departments")] + [(d, d) for d in DEPARTMENTS], validators=[Optional()])
    search = StringField("Search", validators=[Optional(), Length(max=200)])
    submit = SubmitField("Filter")


def generate_report_number():
    year = datetime.now().strftime("%Y")
    count = Report.query.filter(Report.report_number.like(f"USAC-{year}-%")).count()
    return f"USAC-{year}-{str(count + 1).zfill(4)}"


def allowed_file(filename):
    return "." in filename and filename.rsplit(".", 1)[1].lower() in ALLOWED_EXTENSIONS


@app.context_processor
def inject_globals():
    return {
        "current_year": datetime.now().year,
        "categories_act": CATEGORIES_ACT,
        "categories_condition": CATEGORIES_CONDITION,
        "current_user": get_current_user(),
        "ROLES": ROLES,
        "DESIGNATIONS": DESIGNATIONS,
    }


@app.route("/")
def login():
    if "user_id" in session:
        return redirect(url_for("dashboard"))
    form = LoginForm()
    if form.validate_on_submit():
        user = User.query.filter_by(username=form.username.data).first()
        if user and user.check_password(form.password.data):
            if user.role == "employee" and not user.is_approved:
                flash("Your account is pending approval by a Safety Officer.", "warning")
                return redirect(url_for("login"))
            session["user_id"] = user.id
            flash(f"Welcome back, {user.full_name}!", "success")
            return redirect(url_for("dashboard"))
        flash("Invalid username or password.", "danger")
    return render_template("login.html", form=form)


@app.route("/register", methods=["GET", "POST"])
def register():
    if "user_id" in session:
        return redirect(url_for("dashboard"))
    form = RegisterForm()
    if form.validate_on_submit():
        user = User(
            full_name=form.full_name.data,
            username=form.username.data,
            email=form.email.data,
            designation=form.designation.data,
            department=form.department.data,
            role="employee",
            is_approved=False,
        )
        user.set_password(form.password.data)
        db.session.add(user)
        db.session.commit()
        flash("Registration submitted. A Safety Officer will approve your account shortly.", "info")
        return redirect(url_for("login"))
    return render_template("register.html", form=form)


@app.route("/logout")
def logout():
    session.clear()
    flash("You have been logged out.", "info")
    return redirect(url_for("login"))


@app.route("/manage-approvals")
@safety_officer_required
def manage_approvals():
    pending_users = User.query.filter_by(role="employee", is_approved=False).order_by(User.created_at.desc()).all()
    approved_users = User.query.filter_by(role="employee", is_approved=True).order_by(User.full_name.asc()).all()
    return render_template("manage_approvals.html", pending_users=pending_users, approved_users=approved_users)


@app.route("/approve-user/<int:user_id>", methods=["POST"])
@safety_officer_required
def approve_user(user_id):
    user = db.session.get(User, user_id)
    if not user or user.is_safety_officer:
        flash("Invalid user.", "danger")
        return redirect(url_for("manage_approvals"))
    user.is_approved = True
    db.session.commit()
    flash(f"Account for {user.full_name} has been approved.", "success")
    return redirect(url_for("manage_approvals"))


@app.route("/revoke-user/<int:user_id>", methods=["POST"])
@safety_officer_required
def revoke_user(user_id):
    user = db.session.get(User, user_id)
    if not user or user.is_safety_officer:
        flash("Invalid user.", "danger")
        return redirect(url_for("manage_approvals"))
    user.is_approved = False
    db.session.commit()
    flash(f"Approval for {user.full_name} has been revoked.", "warning")
    return redirect(url_for("manage_approvals"))


@app.route("/create-safety-officer", methods=["GET", "POST"])
@safety_officer_required
def create_safety_officer():
    if request.method == "POST":
        full_name = request.form.get("full_name", "").strip()
        username = request.form.get("username", "").strip()
        email = request.form.get("email", "").strip()
        designation = request.form.get("designation", "").strip()
        department = request.form.get("department", "").strip()
        password = request.form.get("password", "")

        if not all([full_name, username, email, designation, department, password]):
            flash("All fields are required.", "danger")
            return redirect(url_for("create_safety_officer"))

        if User.query.filter_by(username=username).first():
            flash("Username already taken.", "danger")
            return redirect(url_for("create_safety_officer"))

        if User.query.filter_by(email=email).first():
            flash("Email already registered.", "danger")
            return redirect(url_for("create_safety_officer"))

        user = User(
            full_name=full_name,
            username=username,
            email=email,
            designation=designation,
            department=department,
            role="safety_officer",
            is_approved=True,
        )
        user.set_password(password)
        db.session.add(user)
        db.session.commit()
        flash(f"Safety Officer account for {full_name} created successfully.", "success")
        return redirect(url_for("manage_approvals"))

    return render_template("create_safety_officer.html")


@app.route("/admin/users")
@admin_required
def admin_users():
    users = User.query.order_by(User.created_at.desc()).all()
    return render_template("admin_users.html", users=users)


@app.route("/admin/delete-user/<int:user_id>", methods=["POST"])
@admin_required
def admin_delete_user(user_id):
    user = db.session.get(User, user_id)
    if not user:
        flash("User not found.", "danger")
        return redirect(url_for("admin_users"))
    if user.is_admin:
        admin_count = User.query.filter_by(role="admin").count()
        if admin_count <= 1:
            flash("Cannot delete the last admin account.", "danger")
            return redirect(url_for("admin_users"))
    db.session.delete(user)
    db.session.commit()
    flash(f"User {user.full_name} has been deleted.", "warning")
    return redirect(url_for("admin_users"))


@app.route("/admin/toggle-role/<int:user_id>", methods=["POST"])
@admin_required
def admin_toggle_role(user_id):
    user = db.session.get(User, user_id)
    if not user or user.is_admin:
        flash("Cannot change admin role from here.", "danger")
        return redirect(url_for("admin_users"))
    if user.role == "employee":
        user.role = "safety_officer"
        user.is_approved = True
        flash(f"{user.full_name} promoted to Safety Officer.", "success")
    elif user.role == "safety_officer":
        user.role = "employee"
        flash(f"{user.full_name} demoted to Employee.", "warning")
    db.session.commit()
    return redirect(url_for("admin_users"))


@app.route("/dashboard")
@login_required
def dashboard():
    total_reports = Report.query.count()
    unsafe_acts = Report.query.filter_by(report_type="Unsafe Act").count()
    unsafe_conditions = Report.query.filter_by(report_type="Unsafe Condition").count()
    open_reports = Report.query.filter(Report.status.in_(["Open", "Under Investigation", "Corrective Action In Progress"])).count()
    closed_reports = Report.query.filter_by(status="Closed").count()
    critical_reports = Report.query.filter_by(severity="Critical").filter(
        Report.status.in_(["Open", "Under Investigation", "Corrective Action In Progress"])
    ).count()
    recent_reports = Report.query.order_by(Report.created_at.desc()).limit(5).all()

    severity_counts = {}
    for s in SEVERITY_LEVELS:
        severity_counts[s] = Report.query.filter_by(severity=s).count()

    dept_counts = {}
    for d in DEPARTMENTS:
        c = Report.query.filter_by(department=d).count()
        if c > 0:
            dept_counts[d] = c

    monthly_data = {}
    for i in range(5, -1, -1):
        month_date = datetime.now().replace(day=1)
        for _ in range(i):
            if month_date.month == 1:
                month_date = month_date.replace(year=month_date.year - 1, month=12)
            else:
                month_date = month_date.replace(month=month_date.month - 1)
        key = month_date.strftime("%b %Y")
        monthly_data[key] = Report.query.filter(
            db.extract("year", Report.created_at) == month_date.year,
            db.extract("month", Report.created_at) == month_date.month
        ).count()

    return render_template("index.html",
        total_reports=total_reports,
        unsafe_acts=unsafe_acts,
        unsafe_conditions=unsafe_conditions,
        open_reports=open_reports,
        closed_reports=closed_reports,
        critical_reports=critical_reports,
        recent_reports=recent_reports,
        severity_counts=severity_counts,
        dept_counts=dept_counts,
        monthly_data=monthly_data
    )


@app.route("/reports")
def reports_list():
    form = FilterForm(request.args, meta={"csrf": False})
    query = Report.query

    user = get_current_user()
    if user and not user.can_edit_reports:
        query = query.filter_by(user_id=user.id)

    report_type = request.args.get("report_type", "")
    severity = request.args.get("severity", "")
    status = request.args.get("status", "")
    department = request.args.get("department", "")
    search = request.args.get("search", "")

    if report_type:
        query = query.filter_by(report_type=report_type)
    if severity:
        query = query.filter_by(severity=severity)
    if status:
        query = query.filter_by(status=status)
    if department:
        query = query.filter_by(department=department)
    if search:
        search_filter = f"%{search}%"
        query = query.filter(
            db.or_(
                Report.title.ilike(search_filter),
                Report.description.ilike(search_filter),
                Report.location.ilike(search_filter),
                Report.reported_by.ilike(search_filter),
                Report.report_number.ilike(search_filter)
            )
        )

    page = request.args.get("page", 1, type=int)
    per_page = 15
    pagination = query.order_by(Report.created_at.desc()).paginate(page=page, per_page=per_page)

    return render_template("reports.html", pagination=pagination, form=form, filters={
        "report_type": report_type,
        "severity": severity,
        "status": status,
        "department": department,
        "search": search
    })


@app.route("/reports/new", methods=["GET", "POST"])
@login_required
def new_report():
    form = ReportForm()
    form.category.choices = [(c, c) for c in CATEGORIES_ACT]

    if form.validate_on_submit():
        user = get_current_user()
        assigned_to = request.form.get("assigned_to", "").strip()
        if not assigned_to:
            flash("Please specify who this report is assigned to.", "danger")
            return render_template("new_report.html", form=form)
        photo_filename = None
        if form.photo.data and allowed_file(form.photo.data.filename):
            ext = form.photo.data.filename.rsplit(".", 1)[1].lower()
            photo_filename = f"{uuid.uuid4().hex}.{ext}"
            form.photo.data.save(os.path.join(app.config["UPLOAD_FOLDER"], photo_filename))

        report = Report(
            report_number=generate_report_number(),
            report_type=form.report_type.data,
            title=form.title.data,
            description=form.description.data,
            category=form.category.data,
            severity=form.severity.data,
            status="Open",
            department=form.department.data,
            location=form.location.data,
            date_observed=form.date_observed.data,
            reported_by=form.reported_by.data,
            contact_email=form.contact_email.data or None,
            immediate_action=form.immediate_action.data or None,
            photo_filename=photo_filename,
            user_id=user.id,
            assigned_to=assigned_to,
        )
        db.session.add(report)
        db.session.commit()
        flash(f"Report {report.report_number} created successfully.", "success")
        return redirect(url_for("view_report", report_id=report.id))

    return render_template("new_report.html", form=form)


@app.route("/reports/<int:report_id>")
def view_report(report_id):
    report = Report.query.get_or_404(report_id)
    user = get_current_user()
    if not user:
        flash("Please log in to view reports.", "warning")
        return redirect(url_for("login"))
    if not user.can_edit_reports and report.user_id != user.id:
        flash("Access denied.", "danger")
        return redirect(url_for("reports_list"))
    edit_form = EditReportForm(obj=report)
    return render_template("view_report.html", report=report, edit_form=edit_form)


@app.route("/reports/<int:report_id>/update", methods=["POST"])
@safety_officer_required
def update_report(report_id):
    report = Report.query.get_or_404(report_id)
    form = EditReportForm()

    if form.validate_on_submit():
        report.status = form.status.data
        report.severity = form.severity.data
        report.root_cause = form.root_cause.data or None
        report.corrective_action = form.corrective_action.data or None
        report.assigned_to = form.assigned_to.data or None
        report.updated_at = datetime.utcnow()
        db.session.commit()
        flash(f"Report {report.report_number} updated successfully.", "success")
    else:
        flash("Error updating report. Please check your input.", "danger")

    return redirect(url_for("view_report", report_id=report.id))


@app.route("/reports/<int:report_id>/delete", methods=["POST"])
@safety_officer_required
def delete_report(report_id):
    report = Report.query.get_or_404(report_id)
    if report.photo_filename:
        photo_path = os.path.join(app.config["UPLOAD_FOLDER"], report.photo_filename)
        if os.path.exists(photo_path):
            os.remove(photo_path)
    db.session.delete(report)
    db.session.commit()
    flash(f"Report {report.report_number} deleted.", "warning")
    return redirect(url_for("reports_list"))


@app.route("/reports/<int:report_id>/reassign", methods=["POST"])
@login_required
def reassign_report(report_id):
    report = Report.query.get_or_404(report_id)
    user = get_current_user()
    if not user:
        flash("Please log in.", "warning")
        return redirect(url_for("login"))
    if report.user_id != user.id and not user.can_edit_reports:
        flash("Only the report owner can reassign.", "danger")
        return redirect(url_for("view_report", report_id=report.id))
    new_assignee = request.form.get("assigned_to", "").strip()
    report.assigned_to = new_assignee or None
    report.reassignment_count += 1
    report.updated_at = datetime.utcnow()
    db.session.commit()
    if new_assignee:
        flash(f"Report reassigned to {new_assignee}.", "success")
    else:
        flash("Assignment cleared.", "info")
    return redirect(url_for("view_report", report_id=report.id))


@app.route("/uploads/<filename>")
def uploaded_file(filename):
    return send_from_directory(app.config["UPLOAD_FOLDER"], filename)


@app.route("/api/dashboard-data")
def dashboard_data():
    severity_counts = {s: Report.query.filter_by(severity=s).count() for s in SEVERITY_LEVELS}
    type_counts = {t: Report.query.filter_by(report_type=t).count() for t in REPORT_TYPES}
    status_counts = {s: Report.query.filter_by(status=s).count() for s in STATUS_OPTIONS}

    monthly_acts = []
    monthly_conditions = []
    labels = []
    for i in range(5, -1, -1):
        month_date = datetime.now().replace(day=1)
        for _ in range(i):
            if month_date.month == 1:
                month_date = month_date.replace(year=month_date.year - 1, month=12)
            else:
                month_date = month_date.replace(month=month_date.month - 1)
        labels.append(month_date.strftime("%b %Y"))
        monthly_acts.append(Report.query.filter_by(report_type="Unsafe Act").filter(
            db.extract("year", Report.created_at) == month_date.year,
            db.extract("month", Report.created_at) == month_date.month
        ).count())
        monthly_conditions.append(Report.query.filter_by(report_type="Unsafe Condition").filter(
            db.extract("year", Report.created_at) == month_date.year,
            db.extract("month", Report.created_at) == month_date.month
        ).count())

    return jsonify({
        "severity": severity_counts,
        "types": type_counts,
        "status": status_counts,
        "monthly_labels": labels,
        "monthly_acts": monthly_acts,
        "monthly_conditions": monthly_conditions,
    })


@app.template_filter("timeago")
def timeago_filter(dt):
    if not dt:
        return ""
    now = datetime.utcnow()
    diff = now - dt
    seconds = diff.total_seconds()
    if seconds < 60:
        return "Just now"
    elif seconds < 3600:
        mins = int(seconds / 60)
        return f"{mins}m ago"
    elif seconds < 86400:
        hours = int(seconds / 3600)
        return f"{hours}h ago"
    else:
        days = int(seconds / 86400)
        return f"{days}d ago"


@app.template_filter("severity_badge")
def severity_badge(severity):
    colors = {
        "Low": "success",
        "Medium": "warning",
        "High": "danger",
        "Critical": "dark",
    }
    color = colors.get(severity, "secondary")
    return f'<span class="badge bg-{color}">{severity}</span>'


@app.template_filter("status_badge")
def status_badge(status):
    colors = {
        "Open": "primary",
        "Under Investigation": "info",
        "Corrective Action In Progress": "warning",
        "Closed": "success",
    }
    color = colors.get(status, "secondary")
    return f'<span class="badge bg-{color}">{status}</span>'


@app.template_filter("type_badge")
def type_badge(report_type):
    if report_type == "Unsafe Act":
        return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Unsafe Act</span>'
    return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Unsafe Condition</span>'


with app.app_context():
    db.create_all()
    os.makedirs(app.config["UPLOAD_FOLDER"], exist_ok=True)

if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5003))
    app.run(host="0.0.0.0", port=port, debug=True)
