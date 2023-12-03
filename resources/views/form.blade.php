<htlm>
    <head>
        <title>Login Form</title>
    </head>
    <body>
        
        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{$error}}</li>
                @endforeach
            </ul>
        @endif

        <form action="/form" method="post">
            @csrf
            <label>Username : @error('username') {{$message}} @enderror 
                <input type="text" name="username" value="{{old('username')}}"></label> <br>
            <label>Password : @error('password') {{$message}} @enderror 
                <input type="password" name="password" value="{{old('password')}}"></label> <br>
            <input type="submit" value="Login">
        </form>

    </body>
</htlm>