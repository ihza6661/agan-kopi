## 🐛 Known Issues & Solutions

### 1. CSRF Token Mismatch
**Problem**: CSRF token mismatch setelah logout → login  
**Solution**: Sudah fixed! Token refresh otomatis menggunakan Inertia shared props

### 2. PostgreSQL vs MySQL Compatibility
**Problem**: Query MySQL tidak kompatibel dengan PostgreSQL  
**Solution**: Sudah fixed! Menggunakan driver detection untuk syntax compatibility

### 3. Shift Opening Cash Display
**Problem**: Opening cash tidak muncul di dialog End Shift  
**Solution**: Sudah fixed! Display menunjukkan: opening cash + sales cash

### 4. Unused Import Warning
**Problem**: TypeScript warning `fetchWithCsrf` not used di `Cashier/Index.tsx:47`  
**Status**: Minor issue, tidak mempengaruhi functionality

## 🔐 Security Features

- **CSRF Protection**: Token-based CSRF protection pada semua form
- **HTTPS Enforcement**: Force HTTPS di production
- **Content Security Policy**: CSP headers untuk XSS protection
- **SQL Injection Prevention**: Eloquent ORM dengan parameter binding
- **XSS Prevention**: Automatic escaping di Blade & React
- **Authentication**: Session-based auth dengan secure cookies
- **Authorization**: Role-based access control (Admin/Kasir)
- **Audit Trail**: Track semua perubahan data penting

## 📊 Performance Optimizations

- **Database Indexing**: Proper indexes pada foreign keys
- **Lazy Loading**: Image & component lazy loading
- **Query Optimization**: Eager loading untuk N+1 query prevention
- **Asset Optimization**: Vite bundling & minification
- **Caching**: Application cache, config cache, route cache
- **Server-Side Pagination**: DataTables server-side processing

## 🤝 Contributing

### Contribution Workflow

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m "feat: add amazing feature"`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open Pull Request

### Commit Message Convention

Menggunakan [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation only
- `style:` - Code style (formatting, no code change)
- `refactor:` - Code refactoring
- `perf:` - Performance improvement
- `test:` - Adding tests
- `chore:` - Maintenance tasks

**Examples**:
```bash
git commit -m "feat: add shift management system"
git commit -m "fix: resolve CSRF token mismatch on logout"
git commit -m "docs: update README with CI/CD documentation"
git commit -m "chore: remove unused Azure deployment workflow"
```

## 📞 Support & Contact

- **Repository**: https://github.com/ihza6661/agan-kopi
- **Issues**: https://github.com/ihza6661/agan-kopi/issues
- **Email**: adibayuluthfiansyah@gmail.com

## 🙏 Acknowledgments

- Laravel Team - Framework
- Inertia.js Team - Server-side rendering
- React Team - UI Library
- shadcn/ui - UI Components
- Tailwind CSS - Styling
- Heroku - Hosting Platform

## 📜 Project History

### Recent Major Updates

**2026-01-24**: CI/CD Implementation
- Implemented complete GitHub Actions pipeline
- Automated testing on every push
- Automated deployment to Heroku
- Created comprehensive documentation

**2026-01-23**: Bug Fixes & Improvements
- Fixed PostgreSQL compatibility issues
- Fixed CSRF token mismatch on account switch
- Fixed shift opening cash display
- Established branch strategy (main/production)

**2026-01-22**: Core Features
- Added shift management system
- Added transaction confirmation workflow
- Added audit trail tracking
- Enhanced security measures