# SmartSPE – Smart Self & Peer Evaluation Moodle Plugin

A Moodle activity plugin designed to streamline self and peer evaluations for group-based coursework, while incorporating sentiment analysis and statistical analysis to enhance evaluation reliability and lecturer insights.

## 📌 Overview

SmartSPE is a custom Moodle module developed for Murdoch University as a Final Year Project. The plugin allows lecturers to create and manage peer evaluation activities for student group projects.

Students evaluate themselves and their teammates through:

Quantitative Likert-scale questions
Qualitative written comments

The system then performs:

Sentiment analysis on textual comments
Consistency checks between scores and comments
Statistical analysis across evaluations

The goal is to create a more intelligent, fair, and insightful peer evaluation system directly integrated into Moodle.

## ✨ Features
### 👨‍🏫 Lecturer / Unit Coordinator Features
Create SmartSPE activities inside Moodle
Select evaluation questions from Moodle Question Bank
Configure submission period
View evaluation summaries
Access sentiment analysis results
Download reports
Monitor consistency and anomalies in evaluations
### 👨‍🎓 Student Features
Evaluate themselves and teammates
Submit Likert-scale ratings
Write peer feedback comments
Autosave evaluation progress
Resume incomplete evaluations
### 🤖 AI & Analysis Features
Sentiment analysis on peer comments
Classification:
Positive
Neutral
Negative
Null / Invalid
Consistency checking between:
Numerical ratings
Written comments
Statistical analysis across team evaluations

## 🧩 Architecture

SmartSPE follows Moodle’s plugin architecture and separates responsibilities into:

UI Rendering
Business Logic
Database Management
Event Handling
Persistence
Sentiment & Statistical Analysis
### 🔄 Core Flow
Student/Teacher
       ↓
UI Layer (Mustache Templates + Renderer)
       ↓
View Controller (view.php)
       ↓
Quiz Manager (Core Logic)
       ↓
Handlers / Database Classes
       ↓
Moodle Database
### 🖥️ Technologies Used
Technology	Purpose
PHP	Moodle plugin backend
Moodle API	LMS integration
Mustache	Moodle templating engine
MySQL / MariaDB	Database
HTML/CSS	Frontend UI
Python	Sentiment & statistical analysis
Virtual Machine	Moodle development environment
### 🎨 UI Components
Student Interface
Evaluation form
Rating dropdowns
Comment textbox
Autosave support
Submission status
Lecturer Interface
Evaluation dashboard
Student summaries
Sentiment indicators
Download reports
Question management
### 🧠 Sentiment Analysis

The plugin analyses peer comments to determine:

Positive sentiment
Neutral sentiment
Negative sentiment
Invalid / null evaluation

The sentiment result is compared against numerical scores to identify inconsistencies or suspicious evaluations.

### 📊 Statistical Analysis

Examples include:

Average scores
Team evaluation consistency
Score variance
Outlier detection
Sentiment-score mismatch analysis
## ⚙️ Installation
1. Copy Plugin into Moodle

Place the plugin folder into:

moodle/mod/smartspe
2. Visit Moodle Notifications Page

Navigate to:

Site Administration → Notifications

Moodle will automatically detect and install the plugin.

3. Configure Capabilities

Ensure proper permissions are assigned:

Students can submit evaluations
Lecturers can manage and view reports
## 🚀 Development Setup
Requirements
Moodle 4.x
PHP 8.x
MySQL / MariaDB
Apache / Nginx
Virtual Machine (provided development environment)
Recommended Tools
Visual Studio Code
Moodle Developer Docs
Moodle Plugin Skeleton Generator
## 🧪 Testing

Current testing includes:

Group retrieval
Evaluation submission
Autosave functionality
Question rendering
Renderer & template integration
Role-based access testing
## 📚 Moodle APIs Used
Moodle Forms API
Renderer API
Mustache Templates
Access API
Events API
Database API
Groups API
Question Bank API
## 🔒 Security Considerations
Moodle capability checks
require_login() authentication
Context validation
Moodle parameter sanitisation (required_param)
Database abstraction layer usage
## 👥 Contributors
Role	Responsibility
UI Developer	Frontend UI, templates, renderer integration
Core Logic Developer	Business logic & handlers
Database Developer	ERD & install.xml
AI/Analysis Developer	Sentiment & statistical analysis
## 📖 References
Moodle Developer Documentation
Moodle Plugin Development Guide
Moodle Question Bank API
Moodle Renderer API
Moodle Mustache Templates

## 📄 License

This project is developed for academic purposes under Murdoch University Final Year Project requirements.
