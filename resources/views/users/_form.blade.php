@csrf
<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nama</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $user->name ?? '') }}" maxlength="255" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12 col-md-6">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email', $user->email ?? '') }}" maxlength="255" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="password" class="form-label">Kata Sandi</label>
        <input id="password" name="password" type="password"
            class="form-control @error('password') is-invalid @enderror" {{ isset($user) ? '' : 'required' }}>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @isset($user)
            <small class="text-muted">Kosongkan jika tidak ingin mengubah kata sandi.</small>
        @endisset
    </div>
    <div class="col-12 col-md-6">
        <label for="password_confirmation" class="form-label">Konfirmasi Kata Sandi</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control">
    </div>

    <div class="col-12 col-md-6">
        <label for="role" class="form-label">Peran</label>
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" required>
            <option value="">Pilih peran</option>
            <option value="admin" @selected(old('role', $user->role ?? '') === 'admin')>Admin</option>
            <option value="cashier" @selected(old('role', $user->role ?? '') === 'cashier')>Kasir</option>
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
