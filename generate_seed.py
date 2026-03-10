import random
import datetime

# --- CONFIGURATION ---
ADMIN_HASH = "$2y$10$d.e4U/TQ/3XM6CELuJe9OuCQhKFjZuKPcp4HKJ.48JFWxJkT7Bs0O" # Admin@123
COMMON_HASH = "$2y$10$Np6Y6skeO9FaXcSflR9gMe1By2sQOrVZIp87RIacjytw9DsZEQuc." # Password@123
OUTPUT_FILE = "database/lab_allocation_system.sql"

# 3 Classes (Departments) as requested
DEPARTMENTS = [
    ("CS", "Computer Science"),
    ("IT", "Information Technology"),
    ("AI", "AI & DS") 
]

# Specific Subject list from user request: (Code, Name, Lang, LabID)
# Note: Lab IDs provided: 1, 2, 3. We will assume:
# 1: Main Computer Lab
# 2: AI Research Lab
# 3: Multimedia Lab
SUBJECTS_DATA = [
    ('CS101', 'C Programming', 'C', 2),
    ('CS102', 'Advanced C', 'C', 1),
    ('CS201', 'C++ Programming', 'C++', 1),
    ('CS202', 'Data Structures', 'C++', 3),
    ('IT301', 'Java Programming', 'Java', 1),
    ('IT302', 'Advanced Java', 'Java', 1),
    ('IT401', 'Python Basics', 'Python', 1),
    ('AI402', 'Machine Learning', 'Python', 1),
    ('AI501', 'Deep Learning', 'Python', 2),
    ('AI502', 'RPA Tools', 'Python', 1),
    ('WEB601', 'HTML & CSS', 'HTML', 3),
    ('WEB602', 'JavaScript Frameworks', 'JavaScript', 3),
    ('WEB603', 'NodeJS Backend', 'NodeJS', 1),
    ('DB701', 'SQL Fundamentals', 'SQL', 3),
    ('DB702', 'RDBMS Concepts', 'SQL', 2),
    ('CLD801', 'Cloud Computing S3', 'Cloud', 2)
]

# EXACTLY 10 STAFF NAMES
STAFF_NAMES = [
    "Dr. Muruganandham", "Prof. Ezhil Selvan", "Dr. S. Karthikeyan", "Prof. R. Lakshmi", 
    "Dr. M. Senthil Kumar", "Prof. P. Gowri", "Dr. K. Balasubramanian", "Prof. J. Geetha",
    "Dr. R. Venkatesh", "Prof. S. Kavitha"
]

BOY_NAMES = ["Arun", "Balaji", "Chandru", "Dinesh", "Elango", "Ganesh", "Hari", "Ilango", "Jayakumar", "Karthik", "Lakshman", "Manikandan", "Muthu", "Naveen", "Prabhu", "Prakash", "Raja", "Ramesh", "Saravanan", "Senthil", "Siva", "Subramani", "Surya", "Thirumal", "Vasanth", "Vijay", "Vikram", "Yogesh", "Aakash", "Deepak", "Gokul", "Vignesh", "Sanjay", "Ajith", "Ashwin"]
GIRL_NAMES = ["Abirami", "Bhavani", "Chitra", "Divya", "Eswari", "Gayathri", "Hema", "Indhu", "Janani", "Kavitha", "Lakshmi", "Malathi", "Meena", "Nandhini", "Priya", "Rajeshwari", "Revathi", "Sangeetha", "Shanthi", "Sindhu", "Suganya", "Thamarai", "Uma", "Vanitha", "Vidya", "Yamuna", "Anitha", "Deepa", "Karthika", "Mahalakshmi", "Pooja", "Preethi"]

SURNAMES = ["Kumar", "Rajan", "Natc", "Pillai", "Gounder", "Chettiar", "Mudaliar", "Naidu", "Reddy", "Iyer", "Iyengar", "Devar", "Nadar", "Rao", "Set", "Sam", "Vel", "Swamy", "Moorthy", "Chandran", "Sundaram", "Krishnan", "Narayanan", "Rangan", "Balaji"]

QUESTIONS_POOL = {
    "C": [
        ("Sum of Two Numbers", "Write a C program that prompts the user for two integers and displays their sum.", "Easy"),
        ("Factorial", "Write a C program to calculate the factorial of a given positive integer.", "Medium"),
        ("Fibonacci Series", "Write a C program to generate the Fibonacci series up to n terms.", "Medium"),
        ("Palindrome Check", "Write a C program to check whether a given number or string is a palindrome.", "Medium"),
        ("Prime Number", "Write a C program to check if a number is prime.", "Medium"),
        ("Array Element Sum", "Write a C program to find the sum of all elements in an integer array.", "Easy"),
        ("Matrix Multiplication", "Write a C program to multiply two matrices.", "Hard"),
        ("Pointer Swap", "Write a C program to swap two numbers using pointers.", "Medium"),
        ("Student Structure", "Create a structure for a student (name, roll, marks) and display details.", "Medium"),
        ("File Read/Write", "Write a C program that reads a string from the user and writes it to a file.", "Hard"),
        ("String Reversal", "Write a C program to reverse a string without using library functions.", "Medium"),
        ("Bitwise Operators", "Demonstrate the usage of AND, OR, and XOR bitwise operators in C.", "Easy"),
        ("Limits Macro", "Write a C program to print the limits of integer types using limits.h.", "Easy"),
        ("GCD Recursion", "Write a recursive C function to find the Greatest Common Divisor (GCD) of two numbers.", "Medium"),
        ("Bubble Sort", "Implement the Bubble Sort algorithm to sort an array of integers.", "Medium"),
        ("Binary Search", "Implement the Binary Search algorithm to find an element in a sorted array.", "Medium"),
        ("Enum Example", "Write a C program to demonstrate the use of enum for days of the week.", "Easy"),
        ("Union Example", "Write a C program to demonstrate the memory sharing property of a union.", "Easy"),
        ("Command Line Args", "Write a C program that prints all arguments passed via the command line.", "Medium"),
        ("Dynamic Memory", "Use malloc() to allocate memory for an array and free() to release it.", "Hard")
    ],
    "Python": [
        ("List Comprehension", "Write a Python script to create a list of squares for numbers 1 to 10 using list comprehension.", "Easy"),
        ("Dictionary Operations", "Create a dictionary representing a book and demonstrate adding and removing keys.", "Easy"),
        ("Pandas CSV Read", "Write a Python script using Pandas to read a CSV file and print the first 5 rows.", "Medium"),
        ("Numpy Matrix", "Use Numpy to create two specific matrices and calculate their dot product.", "Medium"),
        ("Flask Route", "Write a minimal Flask application with a route that returns 'Hello, World!'.", "Medium"),
        ("Lambda Sorting", "Use a lambda function to sort a list of tuples based on the second element.", "Easy"),
        ("Timing Decorator", "Write a Python decorator that measures and prints the execution time of a function.", "Hard"),
        ("Fibonacci Generator", "Implement a Python generator that yields Fibonacci numbers up to n.", "Medium"),
        ("Try-Except", "Write a Python script that handles division by zero using a try-except block.", "Easy"),
        ("File Context Manager", "Write a Python script to read a file using the 'with' statement.", "Easy"),
        ("Regex Email Extraction", "Write a Python script using the re module to find all email addresses in a text.", "Medium"),
        ("Bank Account Class", "Create a Python class 'BankAccount' with deposit and withdraw methods.", "Medium"),
        ("Inheritance", "Demonstrate inheritance with a base class 'Shape' and derived class 'Circle'.", "Medium"),
        ("Requests Get", "Use the requests library to fetch the content of a URL and print the status code.", "Medium"),
        ("JSON Parsing", "Write a Python script to parse a JSON string into a dictionary and access a value.", "Easy"),
        ("Unittest", "Write a simple unittest case to verify a function that adds two numbers.", "Medium"),
        ("Virtual Env Info", "Write a script that prints the path of the current python executable to check venv.", "Easy"),
        ("Pip Usage", "Write a comment block explaining how to install 'requests' using pip.", "Easy"),
        ("Async Coroutine", "Write a simple async function using asyncio that waits for 1 second and prints a message.", "Hard"),
        ("Simple Regression", "Use scikit-learn to perform a simple linear regression on dummy data.", "Hard")
    ],
    "Java": [
        ("Hello World", "Write a Java program to print 'Hello World'.", "Easy"),
        ("Looping", "Write a Java program to print numbers from 1 to 10 using a for loop.", "Easy"),
        ("Class & Object", "Create a Java class 'Car' with attributes and methods.", "Medium"),
        ("Inheritance", "Demonstrate inheritance in Java with specific Parent and Child classes.", "Medium"),
        ("Interface", "Create a Java interface 'Playable' and implement it in a class.", "Medium"),
        ("Exception Handling", "Demonstrate try-catch block in Java.", "Easy"),
        ("ArrayList", "Write a Java program to add and remove elements from an ArrayList.", "Medium"),
        ("HashMap", "Demonstrate storing key-value pairs in a Java HashMap.", "Medium"),
        ("File IO", "Write a Java program to read from a text file.", "Hard"),
        ("Multithreading", "Create a thread in Java by extending the Thread class.", "Hard"),
        ("JDBC Connection", "Write a code snippet to connect to a MySQL database using JDBC.", "Hard"),
        ("Lambda Expression", "Use a lambda expression to implement a functional interface.", "Medium"),
        ("Stream API", "Use Java Streams to filter a list of integers.", "Hard"),
        ("Generics", "Create a generic class in Java that can store any type of object.", "Medium"),
        ("Serialization", "Demonstrate object serialization in Java.", "Hard"),
        ("Abstract Class", "Create an abstract class and implement its method in a subclass.", "Medium"),
        ("String Manipulation", "Write a Java program to count vowels in a string.", "Easy"),
        ("Polymorphism", "Demonstrate method overriding in Java.", "Medium"),
        ("Static Keyword", "Explain and demonstrate the use of the static keyword.", "Easy"),
        ("Final Keyword", "Explain and demonstrate the use of the final keyword.", "Easy")
    ],
    "JavaScript": [
        ("Console Log", "Write a JavaScript code to print to the console.", "Easy"),
        ("Variables", "Demonstrate let, const, and var usage.", "Easy"),
        ("Arrow Function", "Write an arrow function to add two numbers.", "Easy"),
        ("DOM Manipulation", "Write code to change the text content of an element by ID.", "Medium"),
        ("Event Listener", "Add a click event listener to a button.", "Medium"),
        ("Fetch API", "Use fetch() to get data from a public API.", "Medium"),
        ("Promises", "Create a simple Promise that resolves after 1 second.", "Medium"),
        ("Async/Await", "Rewrite a promise-based function using async/await.", "Medium"),
        ("Map & Filter", "Use map and filter on an array of numbers.", "Medium"),
        ("Destructuring", "Demonstrate object and array destructuring.", "Easy"),
        ("Modules", "Explain how to export and import functions in ES6.", "Medium"),
        ("Closure", "Write a function that demonstrates closure.", "Hard"),
        ("Callback", "Write a function that accepts a callback.", "Medium"),
        ("JSON", "Convert a JS object to JSON string and back.", "Easy"),
        ("Local Storage", "Write to and read from localStorage.", "Easy"),
        ("Class Syntax", "Create a class 'Person' with a constructor.", "Medium"),
        ("Template Literals", "Use template literals to format a string.", "Easy"),
        ("Spread Operator", "Use the spread operator to combine two arrays.", "Easy"),
        ("Error Handling", "Use try-catch in JavaScript.", "Easy"),
        ("NodeJS File Read", "Write a NodeJS script to read a file using fs module.", "Medium")
    ],
    "SQL": [
        ("Select All", "Write a SQL query to select all columns from a table.", "Easy"),
        ("Where Clause", "Write a query to filter records where age > 18.", "Easy"),
        ("Order By", "Write a query to sort results by name in descending order.", "Easy"),
        ("Inner Join", "Write a query to join two tables 'users' and 'orders'.", "Medium"),
        ("Left Join", "Demonstrate a LEFT JOIN between 'students' and 'courses'.", "Medium"),
        ("Count & Group By", "Count the number of users in each city.", "Medium"),
        ("Insert", "Write an INSERT statement to add a new record.", "Easy"),
        ("Update", "Write an UPDATE statement to modify a record.", "Easy"),
        ("Delete", "Write a DELETE statement to remove a record.", "Easy"),
        ("Create Table", "Write a CREATE TABLE statement for a 'products' table.", "Medium"),
        ("Alter Table", "Write a query to add a column to an existing table.", "Medium"),
        ("Primary Key", "Explain and define a Primary Key constraint.", "Easy"),
        ("Foreign Key", "Explain and define a Foreign Key constraint.", "Medium"),
        ("Index", "Write a query to create an index on a column.", "Medium"),
        ("Subquery", "Write a query utilizing a subquery in the WHERE clause.", "Hard"),
        ("Limit", "Write a query to fetch only the first 5 records.", "Easy"),
        ("Distinct", "Write a query to select unique values from a column.", "Easy"),
        ("Like", "Write a query to find names starting with 'A'.", "Easy"),
        ("Aggregate Functions", "Demonstrate SUM, AVG, MIN, MAX.", "Medium"),
        ("Transactions", "Explain BEGIN, COMMIT, and ROLLBACK.", "Hard")
    ],
    "Cloud": [
        ("S3 Bucket", "Explain how to create an S3 bucket.", "Easy"),
        ("EC2 Instance", "Describe steps to launch an EC2 instance.", "Medium"),
        ("IAM Role", "What is an IAM role and how is it used?", "Medium"),
        ("VPC", "Define Virtual Private Cloud (VPC).", "Medium"),
        ("Lambda", "What is AWS Lambda? Write a simple handler.", "Hard"),
        ("RDS", "How to set up a relational database in RDS.", "Medium"),
        ("CloudWatch", "How to monitor logs in CloudWatch.", "Medium"),
        ("Route53", "Explain DNS management with Route53.", "Medium"),
        ("CloudFormation", "What is Infrastructure as Code?", "Hard"),
        ("S3 Static Site", "Host a static website on S3.", "Medium"),
        ("DynamoDB", "Create a table in DynamoDB.", "Medium"),
        ("SQS", "Explain Simple Queue Service.", "Hard"),
        ("SNS", "Explain Simple Notification Service.", "Hard"),
        ("ElasticIP", "What is an Elastic IP?", "Easy"),
        ("Security Group", "Configure a security group for web access.", "Medium")
    ],
    "HTML": [
         ("Basic Structure", "Write the basic HTML5 boilerplate structure.", "Easy"),
         ("Headings", "Demonstrate H1 to H6 tags.", "Easy"),
         ("Paragraph", "Write a paragraph with bold and italic text.", "Easy"),
         ("Links", "Create a hyperlink to google.com.", "Easy"),
         ("Images", "Embed an image with alt text.", "Easy"),
         ("Lists", "Create both ordered and unordered lists.", "Easy"),
         ("Table", "Create a simple table with 2 rows and 2 columns.", "Medium"),
         ("Form", "Create a form with text input and submit button.", "Medium"),
         ("Input Types", "Demonstrate email and password input types.", "Easy"),
         ("Div vs Span", "Explain difference between div and span.", "Medium"),
         ("Attributes", "Show usage of id and class attributes.", "Easy"),
         ("Meta Tags", "Add a viewport meta tag.", "Medium"),
         ("Semantic Tags", "Use header, footer, and section tags.", "Medium"),
         ("Iframe", "Embed a YouTube video using iframe.", "Medium"),
         ("Audio/Video", "Embed an audio file.", "Medium")
    ]
}

def get_random_name():
    if random.choice([True, False]):
        fname = random.choice(BOY_NAMES)
    else:
        fname = random.choice(GIRL_NAMES)
    lname = random.choice(SURNAMES)
    return f"{fname} {lname}"

def generate_sql():
    sql = []
    
    # 1. HEADER & SCHEMA
    sql.append("-- GENERATED SEED DATA FOR GOBI ARTS AND SCIENCE COLLEGE")
    sql.append(f"-- Generated on {datetime.datetime.now()}")
    sql.append("")
    sql.append("SET FOREIGN_KEY_CHECKS = 0;")
    sql.append("")
    
    schema = """
CREATE DATABASE IF NOT EXISTS lab_allocation_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lab_allocation_system;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS submissions;
DROP TABLE IF EXISTS student_allocations;
DROP TABLE IF EXISTS exam_sessions;
DROP TABLE IF EXISTS staff_assignments;
DROP TABLE IF EXISTS student_subjects;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS labs;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS notifications;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') DEFAULT 'admin',
    gender ENUM('Male', 'Female') DEFAULT 'Male',
    avatar_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
    avatar_id INT DEFAULT 1,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
    avatar_id INT DEFAULT 1,
    department VARCHAR(100),
    semester INT,
    system_no INT,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE labs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_name VARCHAR(100) NOT NULL,
    total_systems INT DEFAULT 70,
    location VARCHAR(200),
    seating_layout TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) UNIQUE NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    language VARCHAR(50) NOT NULL,
    lab_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE
);

CREATE TABLE student_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, subject_id)
);

CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    question_title VARCHAR(200) NOT NULL,
    question_text TEXT NOT NULL,
    difficulty ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
    pattern_category VARCHAR(50),
    sample_input TEXT,
    sample_output TEXT,
    expected_approach TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

CREATE TABLE staff_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    lab_id INT NOT NULL,
    subject_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (staff_id, lab_id, subject_id)
);

CREATE TABLE exam_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_code VARCHAR(50) UNIQUE NOT NULL,
    subject_id INT NOT NULL,
    staff_id INT NOT NULL,
    lab_id INT NOT NULL,
    test_type ENUM('Practice', 'Weekly Test', 'Monthly Test', 'Internal Exam') NOT NULL,
    duration_minutes INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    status ENUM('Active', 'Completed', 'Cancelled') DEFAULT 'Active',
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    FOREIGN KEY (lab_id) REFERENCES labs(id) ON DELETE CASCADE
);

CREATE TABLE student_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    system_no INT NOT NULL,
    question_1_id INT NOT NULL,
    question_2_id INT NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_q1 ENUM('Not Started', 'In Progress', 'Submitted') DEFAULT 'Not Started',
    status_q2 ENUM('Not Started', 'In Progress', 'Submitted') DEFAULT 'Not Started',
    FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (question_1_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_2_id) REFERENCES questions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_allocation (session_id, student_id)
);

CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    allocation_id INT NOT NULL,
    question_id INT NOT NULL,
    submitted_code TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    marks DECIMAL(5,2) DEFAULT NULL,
    remarks TEXT,
    evaluated_by INT,
    evaluated_at TIMESTAMP NULL,
    FOREIGN KEY (allocation_id) REFERENCES student_allocations(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    total_marks DECIMAL(5,2) DEFAULT 0,
    remarks TEXT,
    evaluated_by INT,
    evaluated_at TIMESTAMP NULL,
    FOREIGN KEY (session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_role ENUM('admin', 'staff', 'student') NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_role ENUM('admin', 'staff', 'student'),
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT 0,
    icon VARCHAR(50) DEFAULT 'bi-bell',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
    """
    sql.append(schema)
    sql.append("")

    # 2. SEED ADMIN
    sql.append(f"INSERT INTO users (username, password, gender, avatar_id) VALUES ('srinath', '{ADMIN_HASH}', 'Male', 1);")
    sql.append("")

    # 3. SEED LABS
    sql.append("INSERT INTO labs (lab_name, total_systems, location, seating_layout) VALUES")
    sql.append("('Main Computer Lab', 100, 'Main Block First Floor', '10x10 Grid'),")
    sql.append("('AI Research Lab', 60, 'CS Bock Second Floor', 'Workstations'),")
    sql.append("('Multimedia Lab', 60, 'IT Block', 'Rows');")
    sql.append("")

    # 4. SEED STAFF (Exactly 10)
    sql.append("INSERT INTO staff (staff_id, name, email, phone, gender, avatar_id, status, password, department, created_by) VALUES")
    staff_entries = []
    staff_objs = [] 
    
    staff_depts = ["Computer Science"]*4 + ["Information Technology"]*3 + ["AI & DS"]*3
    
    for i, name in enumerate(STAFF_NAMES):
        sid = f"STF{100+i}"
        department = staff_depts[i]
        email = f"{name.lower().replace(' ', '').replace('.', '').replace('dr', '').replace('prof', '')}@gobi.edu"
        phone = f"98765{random.randint(10000, 99999)}"
        gender = 'Female' if any(t in name for t in ['Mrs.', 'Ms.', 'Miss']) or name.split()[-1] in GIRL_NAMES else 'Male'
        avatar_id = random.randint(1, 8)
        
        staff_entries.append(f"('{sid}', '{name}', '{email}', '{phone}', '{gender}', {avatar_id}, 'Active', '{ADMIN_HASH}', '{department}', 1)")
        staff_objs.append({"id": i+1, "name": name, "dept": department})
    sql.append(",\n".join(staff_entries) + ";")
    sql.append("")

    # 5. SEED SUBJECTS
    sql.append("INSERT INTO subjects (subject_code, subject_name, language, lab_id) VALUES")
    subject_entries = []
    subject_objs = []
    for i, sub in enumerate(SUBJECTS_DATA):
        code, name, lang, lab_id = sub
        subject_entries.append(f"('{code}', '{name}', '{lang}', {lab_id})")
        subject_objs.append({"id": i+1, "code": code, "name": name, "lang": lang, "lab_id": lab_id})
    sql.append(",\n".join(subject_entries) + ";")
    sql.append("")
    
    # 6. SEED QUESTIONS
    sql.append("-- Seeding Questions (Specific per Subject)")
    sql.append("INSERT INTO questions (subject_id, question_title, question_text, difficulty, pattern_category, sample_input, sample_output) VALUES")
    q_entries = []
    
    for sub in subject_objs:
        lang = sub["lang"]
        if lang in QUESTIONS_POOL:
            pool = QUESTIONS_POOL[lang]
        elif lang == "C++": pool = QUESTIONS_POOL["C"]
        elif "Java" in lang: pool = QUESTIONS_POOL["Java"]
        elif "Python" in lang: pool = QUESTIONS_POOL["Python"]
        elif "HTML" in lang: pool = QUESTIONS_POOL["HTML"]
        elif "SQL" in lang: pool = QUESTIONS_POOL["SQL"]
        elif "Cloud" in lang: pool = QUESTIONS_POOL["Cloud"]
        else: pool = QUESTIONS_POOL["C"]
             
        num_q = random.randint(15, 20)
        for q_idx in range(num_q):
            q_base = pool[q_idx % len(pool)]
            title = f"{q_base[0]} {q_idx+1}"
            desc = q_base[1]
            diff = q_base[2]
            desc = desc.replace("'", "\\'")
            title = title.replace("'", "\\'")
            q_entries.append(f"({sub['id']}, '{title}', '{desc}', '{diff}', 'General', 'input', 'output')")
            
    sql.append(",\n".join(q_entries) + ";")
    sql.append("")

    # 7. SEED STUDENTS (Exactly 100, 3 Classes)
    sql.append("INSERT INTO students (roll_number, name, email, phone, gender, avatar_id, department, semester, system_no, password) VALUES")
    student_entries = []
    
    current_sys_no = 1
    total_students = 0
    
    class_dist = [
        ("CS", "Computer Science", 34),
        ("IT", "Information Technology", 33),
        ("AI", "AI & DS", 33)
    ]
    
    start_id = 1
    
    for dept_code, dept_name, count in class_dist:
        for i in range(1, count + 1):
            if random.choice([True, False]):
                fname = random.choice(BOY_NAMES)
                lname = random.choice(SURNAMES)
                gender = 'Male'
            else:
                fname = random.choice(GIRL_NAMES)
                lname = random.choice(SURNAMES)
                gender = 'Female'
                
            name = f"{fname} {lname}"
            roll_str = f"{i:03d}"
            roll_no = f"23-{dept_code}-{roll_str}"
            email = f"{roll_no.lower()}@student.gobi.edu"
            phone = f"638{random.randint(1000000, 9999999)}"
            avatar_id = random.randint(1, 8)
            
            semester = 3
            sys_no = current_sys_no
            current_sys_no += 1
            if current_sys_no > 70: current_sys_no = 1
            
            student_entries.append(f"('{roll_no}', '{name}', '{email}', '{phone}', '{gender}', {avatar_id}, '{dept_name}', {semester}, {sys_no}, '{COMMON_HASH}')")
            total_students += 1
            start_id += 1
            
    sql.append(",\n".join(student_entries) + ";")
    sql.append(f"-- Total Students Generated: {total_students}")
    sql.append("")

    # 8. SEED STAFF ASSIGNMENTS (Strict Mapping)
    sql.append("INSERT INTO staff_assignments (staff_id, lab_id, subject_id) VALUES")
    assign_entries = []
    
    def get_staff_by_dept(dept_str):
        return [s for s in staff_objs if dept_str in s['dept']]
    
    for sub in subject_objs:
        sub_code = sub['code']
        sub_id = sub['id']
        lab_id = sub['lab_id']
        
        target_staff = []
        if sub_code.startswith("CS") or sub_code.startswith("DB") or sub_code.startswith("CLD"):
            target_staff = get_staff_by_dept("Computer Science")
        elif sub_code.startswith("IT") or sub_code.startswith("WEB"):
            target_staff = get_staff_by_dept("Information Technology") 
        elif sub_code.startswith("AI"):
             target_staff = get_staff_by_dept("AI & DS")
        
        if not target_staff:
            target_staff = staff_objs
            
        assigned_staff = random.sample(target_staff, k=min(len(target_staff), 2))
        for staff in assigned_staff:
             assign_entries.append(f"({staff['id']}, {lab_id}, {sub_id})")

    sql.append(",\n".join(assign_entries) + ";")
    sql.append("")

    # 9. SEED STUDENT SUBJECTS (New)
    sql.append("INSERT INTO student_subjects (student_id, subject_id) VALUES")
    enroll_entries = []
    
    # IDs 1-34: CS -> CS*, DB*, CLD*
    # IDs 35-67: IT -> IT*, WEB*
    # IDs 68-100: AI -> AI*
    
    cs_range = range(1, 35)      # 1 to 34
    it_range = range(35, 68)     # 35 to 67
    ai_range = range(68, 101)    # 68 to 100
    
    for sid in range(1, 101):
        target_prefixes = []
        if sid in cs_range:
            target_prefixes = ["CS", "DB", "CLD"]
        elif sid in it_range:
            target_prefixes = ["IT", "WEB"]
        elif sid in ai_range:
            target_prefixes = ["AI"]
            
        relevant_subs = [s for s in subject_objs if any(s['code'].startswith(p) for p in target_prefixes)]
        
        if relevant_subs:
            chosen_subs = random.sample(relevant_subs, k=min(len(relevant_subs), random.randint(2, 3)))
            for sub in chosen_subs:
                 enroll_entries.append(f"({sid}, {sub['id']})")
                 
    sql.append(",\n".join(enroll_entries) + ";")
    sql.append("")
    
    sql.append("SET FOREIGN_KEY_CHECKS = 1;")

    return "\n".join(sql)

if __name__ == "__main__":
    content = generate_sql()
    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        f.write(content)
    print(f"Successfully generated SQL file at {OUTPUT_FILE}")
