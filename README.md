# Health Locker 🏥

![Health Locker Banner](https://via.placeholder.com/1200x400/0ea5e9/ffffff?text=Health+Locker)  
*A secure digital vault for your family's medical records powered by AI*

## ✨ Key Features

### 🔐 Secure Health Management
- **Military-Grade Encryption**: All records encrypted with AES-256 at rest and in transit
- **HIPAA-Compliant Storage**: Secure cloud storage with automatic backups
- **Zero-Knowledge Architecture**: Even we can't access your unencrypted data

### 👨‍👩‍👧‍👦 Family Health Hub
- **Unlimited Family Profiles**: Add parents, children, and dependents
- **Granular Permissions**: Control who sees what (e.g., hide sensitive records from kids)
- **Emergency Access**: Designate trusted contacts for emergency situations

### 🤖 AI-Powered Insights
- **Report Simplification**: Transforms complex medical jargon into plain English
- **Trend Analysis**: Visualizes health metrics over time (blood pressure, cholesterol, etc.)
- **Smart Alerts**: Flags abnormal results and suggests next steps

### 🚀 Productivity Boosters
- **Auto-Expiring Shares**: Time-limited access links for doctors
- **OCR Processing**: Extracts text from scanned documents and handwritten notes
- **Medication Tracker**: With dosage reminders and interaction warnings

## 🛠 Tech Stack

### Frontend
| Technology       | Purpose                          |
|------------------|----------------------------------|
| Tailwind CSS 3.3 | Modern utility-first CSS framework |
| Alpine.js        | Lightweight reactivity           |
| FilePond         | Smooth file uploads with preview |
| Chart.js         | Health metric visualizations     |

### Backend
| Technology       | Purpose                          |
|------------------|----------------------------------|
| PHP 8.1+         | Core application logic           |
| Laravel Sanctum  | API authentication               |
| Intervention Image| Image processing library        |
| TCPDF            | PDF generation and processing    |

### AI Services
| Service          | Usage                            |
|------------------|----------------------------------|
| OpenAI API       | Medical report simplification    |
| Google Cloud Vision | OCR and document analysis     |

### Infrastructure
| Component        | Specification                    |
|------------------|----------------------------------|
| Database         | MySQL 8.0 (InnoDB cluster)       |
| Storage          | S3-compatible encrypted storage  |
| Server           | Ubuntu 22.04 LTS (4vCPU/8GB RAM) |

## 🗄 Database Schema (Enhanced)

```mermaid
erDiagram
    users ||--o{ family_members : "1:N"
    family_members ||--o{ medical_records : "1:N"
    users {
        int id PK
        varchar(255) name
        varchar(255) email UNIQUE
        varchar(255) password
        timestamp email_verified_at
        varchar(100) remember_token
        timestamp created_at
        timestamp updated_at
    }
    family_members {
        int id PK
        int user_id FK
        varchar(100) relationship
        varchar(255) full_name
        date date_of_birth
        varchar(20) blood_type
        text known_allergies
        text chronic_conditions
    }
    medical_records {
        int id PK
        int family_member_id FK
        varchar(50) record_type
        date record_date
        varchar(255) file_path
        text ai_summary
        json extracted_data
        timestamp created_at
    }