🏡 Real Estate Platform – Frontend

This is the frontend application for the Real Estate Platform, built with React + Vite + TailwindCSS.
It provides a modern, responsive UI for multiple user roles including Admin, Agents, Buyers, Sellers, Investors, and Renters.

The app connects to the Laravel backend API to manage properties, users, and role-based dashboards.

🚀 Features

🎨 Modern UI

Built with React + TailwindCSS

Responsive layouts for desktop & mobile

Role-based sidebar navigation

🔑 Authentication & Role-Based Access

Login, Register, Forgot Password flows

Different dashboards for Admin, Agent, Buyer, Seller, Investor, Renter

Protected routes with react-router

🏠 Property Management

Browse properties (For Sale, For Rent, Luxury, Commercial, Land)

Search & Filter properties

Favorite / Save properties for later

Add / Edit / Manage properties (for agents/sellers)

📊 Dashboards (Role Based)

Admin → Analytics, User Management, Properties, Reports

Agent → My Listings, Add Property, Clients, Inquiries, Schedule

Buyer → Saved Properties, Inquiries, Alerts

Seller → My Listings, Offers, Documents

Investor → Portfolio, ROI Calculator, Market Trends

Renter → Browse Rentals, Saved Properties, Schedule Tours

🔔 Notifications & Inquiries

Inquiry management

Property alerts

Scheduling system

📂 Tech Stack

Frontend: React + Vite

UI: TailwindCSS + shadcn/ui + lucide-react icons

State Management: Context API / Zustand (optional)

Routing: React Router DOM

Charts: Recharts (for analytics & reports)

Animations: Framer Motion

⚙️ Installation
# Clone the repository
git clone https://github.com/uniksrj/EstateHub.git
cd real-estate-frontend

# Install dependencies
npm install

# Run the app
npm run dev

🔗 Backend Connection

This app requires the Laravel backend API to function.
Make sure you also set up the backend:
👉 Real Estate Backend Repo