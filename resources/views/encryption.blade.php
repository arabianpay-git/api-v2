<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Encryption Tool</title>
</head>
<body>
    <h1>Encryption / Decryption Tool</h1>

    <form action="{{ route('encryption.tool.process') }}" method="POST">
        @csrf
        <label>Text:</label><br>
        <textarea name="text" rows="4" cols="50">{{ old('text', $input ?? '') }}</textarea><br><br>

        <label>Operation:</label><br>
        <select name="operation">
            <option value="encrypt" {{ (old('operation', $operation ?? '') == 'encrypt') ? 'selected' : '' }}>Encrypt</option>
            <option value="decrypt" {{ (old('operation', $operation ?? '') == 'decrypt') ? 'selected' : '' }}>Decrypt</option>
        </select><br><br>

        <button type="submit">Submit</button>
    </form>

    @if (isset($result))
        <h2>Result:</h2>
        <p><strong>{{ ucfirst($operation) }}ed Text:</strong></p>
        <textarea rows="4" cols="50" readonly>{{ $result }}</textarea>
    @endif

    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</body>
</html>
