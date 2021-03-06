<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/', function (Request $request, Response $response, $args) {
    // Render index view
    return $this->view->render($response, 'index.latte');
})->setName('index');

$app->get('/people', function($request, $response) {
    $stmt = $this->db->query('SELECT * FROM person ORDER BY last_name');
    $persons = $stmt->fetchAll();
    $this->view->addParam('persons', $persons);
    return $this->view->render($response, 'people.latte');
})->setName('people');

$app->get('/new-person', function($request, $response) {
    return $this->view->render($response, 'new-person.latte');
})->setName('new-person');

$app->post('/new-person', function($request, $response) {
    $data = $request->getParsedBody();     
    var_dump($data);
    if (empty($data['birth_day'])) {
      $data['birth_day'] = null;
    }
    if (empty($data['height'])) {
      $data['height'] = null;
    }
    $error = '';
    if (empty($data['first_name'])) {
      $error = "first name is required";
    }
    if (empty($data['last_name'])) {
      $error = "last name is required";
    }
    if (empty($data['nickname'])) {
      $error = "nickname is required";
    }
    if (empty($data['gender'])) {
      $error = "gender is required";
    }
    
    if (!$error) {
      try {
          $stmt = $this->db->prepare("INSERT INTO person 
          (first_name, last_name, nickname, birth_day, height, gender) 
          VALUES
          (:fn, :ln, :nick, :bday, :height, :gender)");
          $stmt->bindValue(':fn', $data['first_name']);
          $stmt->bindValue(':ln', $data['last_name']);
          $stmt->bindValue(':nick', $data['nickname']);
          $stmt->bindValue(':bday', $data['birth_day']);
          $stmt->bindValue(':height', $data['height']);
          $stmt->bindValue(':gender', $data['gender']);
          $stmt->execute();
      } catch (Exception $e) {
          if ($e->getCode() == '23505') {
          	 $error = "person exists";
          }
      }
    }
    echo $error;    
    
});

$app->get('/person-profile', function($request, $response) {
  $id = $request->getQueryParam('id');
  
  $stmt = $this->db->prepare('SELECT * FROM person WHERE id_person = :id');
  $stmt->bindValue(':id', $id);
  $stmt->execute();
  
  $person = $stmt->fetch();
  if(empty($person)) {
    exit('No person found!');
  }
  
  return $this->view->render($response, 'person-profile.latte', $person);
})->setName('profile');

$app->post('/person-profile', function($request, $response) {
  $id = $request->getQueryParam('id');
  $data = $request->getParsedBody();
  if (empty($data['first_name'])) {
    $error = "first name is required";
  }
  if (!$error) {    
    $stmt = $this->db->prepare('UPDATE person SET first_name = :fn, 
                                                last_name = :ln,
                                                nickname = :nn
                              WHERE id_person = :id');
    $stmt->bindValue(':fn', $data['first_name']);
    $stmt->bindValue(':ln', $data['last_name']);
    $stmt->bindValue(':nn', $data['nickname']);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    return $response->withHeader('Location', $this->router->pathFor('people'));
  }
  echo $error;
});

$app->post('/delete', function($request, $response) {
  $id = $request->getQueryParam('id');
  
  $stmt = $this->db->prepare('DELETE FROM person WHERE id_person = :id');
  $stmt->bindValue(':id', $id);
  $stmt->execute();  

  return $response->withHeader('Location', $this->router->pathFor('people'));
})->setName('delete');



