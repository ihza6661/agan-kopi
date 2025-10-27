@csrf
<fieldset class="mb-3">
    <label for="name" class="form-label">Nama Kategori</label>
    <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $category->name ?? '') }}" maxlength="100" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</fieldset>

<fieldset class="mb-3">
    <label for="description" class="form-label">Deskripsi</label>
    <textarea id="description" name="description" rows="3"
        class="form-control @error('description') is-invalid @enderror" maxlength="1000">{{ old('description', $category->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</fieldset>
