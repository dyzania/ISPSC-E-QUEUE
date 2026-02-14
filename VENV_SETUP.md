# Python Virtual Environment Setup Guide

This guide explains how to set up the Python environment for the Sentiment Analysis component of the E-Queue System after cloning the repository to a new device.

## Prerequisites

- **Python 3.8+** installed and added to your system PATH.
- **Git** installed.

## 1. Clone the Repository

If you haven't already, clone the project:

```bash
git clone https://github.com/dyzania/antigravity.git equeue-system
cd equeue-system
```

## 2. Create the Virtual Environment

Run the following command in the project root directory (`equeue-system/`) to create a virtual environment named `.venv`:

### Windows

```cmd
python -m venv .venv
```

### macOS / Linux

```bash
python3 -m venv .venv
```

## 3. Activate the Virtual Environment

You need to activate the environment before installing dependencies.

### Windows (Command Prompt)

```cmd
.venv\Scripts\activate.bat
```

### Windows (PowerShell)

```powershell
.venv\Scripts\Activate.ps1
```

_Note: If you get a permission error, run `Set-ExecutionPolicy RemoteSigned -Scope CurrentUser` in PowerShell first._

### macOS / Linux

```bash
source .venv/bin/activate
```

**Success Check:** Your terminal prompt should now show `(.venv)`.

## 4. Install Dependencies

Install the required Python packages from the `requirements.txt` file located in the `sentiment_analysis` folder:

```bash
pip install -r sentiment_analysis/requirements.txt
```

This will install:

- FastAPI
- Uvicorn
- Transformers
- Torch
- Pydantic
- and other necessary libraries.

## 5. Configure Environment Variables (.env)

Since `.env` is ignored by git, you must create it manually.

1. Create a file named `.env` in the project root (`equeue-system/`).
2. Add your local configuration key-values. Example:

```env
# E-Queue System Configuration

# Database
DB_HOST=localhost
DB_NAME=equeue_system
DB_USER=root
DB_PASS=

# AI & API Keys
OPENROUTER_API_KEY=your_openrouter_key_here
OPENROUTER_API_URL=https://openrouter.ai/api/v1/chat/completions
AI_MODEL=stepfun/step-3.5-flash:free

# Email Settings (SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@equeue.com
MAIL_FROM_NAME="E-Queue System"
```

## 6. Run the Sentiment Analysis Server

With the `.venv` activated, start the server:

```bash
cd sentiment_analysis
uvicorn app:app --host 127.0.0.1 --port 8000 --reload
```

## 7. Run the PHP Application

Ensure your XAMPP/Apache server is running and pointing to the `public/` directory.

---

**Troubleshooting:**

- **"python not found"**: Ensure Python is in your system PATH.
- **ImportError**: Make sure you activated the `.venv` _before_ running `pip install` or `uvicorn`.
