<?php
    class database {
        function opencon(): PDO {
            return new PDO('mysql:host=localhost;
            dbname=lms_app_db2',
            username: 'root',
            password: '');

            require_once('classes/database.php');
            $con = new database();
            $data = $con->opencon();
        }

        function signupUser($firstname, $lastname, $birthday, $email, $sex, $phone, $username, $password, $profile_picture_path) {
                $con = $this->opencon();
                try {
                $con->beginTransaction();

                // Insert into Users table
                $stmt = $con->prepare("INSERT INTO Users (user_FN, user_LN, user_birthday, user_sex, user_email, user_phone, user_username, user_password) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$firstname, $lastname, $birthday, $sex, $email, $phone, $username, $password]);

                //Get the newly inserted user_id
                $userId = $con->lastInsertId();

                //Insert into users_pictures table
                $stmt = $con->prepare("INSERT INTO users_pictures (user_id, user_pic_url) VALUES (?,?)");
                $stmt->execute([$userId, $profile_picture_path]);

                $con->commit();
                return $userId; //return user_id for further use (like inserting address)
            } catch(PDOException $e) {
                $con->rollBack();
                return false;
            }

        }

        function insertAddress($user_Id, $street, $barangay, $city, $province) {
            $con = $this->opencon();
            try {
                $con->beginTransaction();

                //Insert into Address table
                $stmt = $con->prepare("INSERT INTO Address (ba_street, ba_barangay, ba_city, ba_province) VALUES (?,?,?,?)");
                $stmt->execute([$street, $barangay, $city, $province]);

                //Get the newly inserted address_id
                $addressId = $con->lastInsertId();

                //Link User and Address into Users_Address table
                $stmt = $con->prepare("INSERT INTO Users_Address (user_id, address_id) VALUES (?,?)");
                $stmt->execute([$user_Id, $addressId]);

                $con->commit();
                return true;

            } catch(PDOException $e) {
                $con->rollBack();
                return false;
            }
        }
        function loginUser($email, $password) {
        $con = $this->opencon();
        try {
            $stmt = $con->prepare("SELECT * From Users WHERE user_email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if($user && password_verify($password, $user['user_password'])) {
                return $user;
            } else {
                return false;
            }
            } catch (PDOException $e) {
                $con->rollBack();
                return false;
            }
        }

        function addAuthor($author_FN, $author_LN, $author_Bday, $author_Nation) {
                $con = $this->opencon();
                try {
                $con->beginTransaction();

                // Insert into Users table
                $stmt = $con->prepare("INSERT INTO Authors (author_FN, author_LN, author_birthday, author_nat) VALUES (?,?,?,?)");
                $stmt->execute([$author_FN, $author_LN, $author_Bday, $author_Nation]);

                //Get the newly inserted user_id
                $authorId = $con->lastInsertId();

                $con->commit();
                return $authorId; //return user_id for further use (like inserting address)
            } catch(PDOException $e) {
                $con->rollBack();
                return false;
            }

        }

        function addGenres($genreName) {
                $con = $this->opencon();
                try {
                $con->beginTransaction();

                // Insert into Users table
                $stmt = $con->prepare("INSERT INTO Genres (genre_name) VALUES (?)");
                $stmt->execute([$genreName]);

                //Get the newly inserted user_id
                $genreId = $con->lastInsertId();

                $con->commit();
                return $genreId; //return user_id for further use (like inserting address)
            } catch(PDOException $e) {
                $con->rollBack();
                return false;
            }

        }

        function viewAuthors() {
            $con = $this->opencon();
            return $con->query("SELECT * FROM Authors")
            ->fetchAll();
        }

        function viewAuthorsID($id) {
            $con = $this->opencon();
            $stmt = $con->prepare("SELECT * FROM Authors WHERE author_id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        function updateAuthor($author_FN, $author_LN, $author_Bday, $author_Nation, $id) {
            try {
                $con = $this->opencon();
                $con->beginTransaction();
                $query = $con->prepare("UPDATE authors SET author_FN = ? , author_LN = ? , author_birthday = ? , author_nat = ? WHERE author_id = ? ");
                $query->execute([$author_FN, $author_LN, $author_Bday, $author_Nation, $id]);
                // Update successful
                $con->commit();
                return true;
            } catch (PDOException $e) {
                // Handle the exception (e.g., log error, return false, etc.)
                $con->rollBack();
                return false; // Update failed
            }
        }

        function viewGenres() {
            $con = $this->opencon();
            return $con->query("SELECT * FROM Genres")
            ->fetchAll();
        }

        function viewGenresID($id) {
            $con = $this->opencon();
            $stmt = $con->prepare("SELECT * FROM Genres WHERE genre_id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        function updateGenre($genre_name, $id) {
            try {
                $con = $this->opencon();
                $con->beginTransaction();
                $query = $con->prepare("UPDATE Genres SET genre_name = ? WHERE genre_id = ? ");
                $query->execute([$genre_name, $id]);
                // Update successful
                $con->commit();
                return true;
            } catch (PDOException $e) {
                // Handle the exception (e.g., log error, return false, etc.)
                $con->rollBack();
                return false; // Update failed
            }
        }

        
        function addBook($title, $isbn, $pubyear, $quantity, $genre_ids = [], $author_ids = []) {
            $con = $this->opencon();
            try {
                $con->beginTransaction();

                //Insert into Books table
                $stmt = $con->prepare("INSERT INTO Books (book_title, book_isbn, book_pubyear, quantity_avail) VALUES (?,?,?,?)");
                $stmt->execute([$title, $isbn, $pubyear, $quantity]);
                $book_id = $con->lastInsertId();

                // Insert into Books_Genres table
                foreach ($genre_ids as $genre_id) {
                    $stmt = $con->prepare("INSERT INTO Genre_Books (genre_id, book_id) VALUES (?,?)");
                    $stmt->execute([$genre_id, $book_id]);
                }


                // Insert into Books_Authors table
                foreach ($author_ids as $author_id) {
                    $stmt = $con->prepare("INSERT INTO Book_Authors (book_id, author_id) VALUES (?,?)");
                    $stmt->execute([$book_id, $author_id]);
                }

                // Insert into Book_Copy table for each quantity
                for ($i = 0; $i < $quantity; $i++) {
                    $stmt = $con->prepare("INSERT INTO Book_Copy (book_id, is_available) VALUES (?, 1)");
                    $stmt->execute([$book_id]);
                }

                $con->commit();
                return $book_id;
            } catch (PDOException $e) {
                $con->rollBack();
                return false; // Update failed
            }
        }
    }
    
    
?>