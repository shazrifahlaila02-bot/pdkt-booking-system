"""End-to-end PHP/MySQL check; creates and removes only its own test records."""
import http.cookiejar
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
import uuid
from datetime import date, timedelta

BASE = 'http://127.0.0.1:8080/'
TAG = uuid.uuid4().hex
ADMIN = f'admin-{TAG}@example.test'
STAFF = f'staff-{TAG}@example.test'
PASSWORD = uuid.uuid4().hex

def sql(statement):
    return subprocess.check_output(['docker','compose','exec','-T','db','mariadb','-uroot','booking','-Nse',statement],text=True).strip()

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def get(c):
    return c.open(BASE).read().decode()

def post(c, action, expected=200, **fields):
    token=re.search('name="csrf" value="([^"]+)"',get(c)).group(1)
    fields=dict(csrf=token,action=action,**fields)
    request=urllib.request.Request(BASE,urllib.parse.urlencode(fields).encode())
    try:
        with c.open(request) as response:
            code,body=response.status,response.read().decode()
    except urllib.error.HTTPError as error:
        code,body=error.code,error.read().decode()
    assert code==expected,(action,code,body)
    return body

admin,staff=client(),client()
try:
    # Seed a unique test administrator without changing an existing operator account.
    hashed=subprocess.check_output(['docker','compose','exec','-T','web','php','-r','echo password_hash($argv[1], PASSWORD_DEFAULT);',PASSWORD],text=True).strip()
    if sql("SELECT COUNT(*) FROM users WHERE role='admin'") == '0':
        post(admin,'setup',name='Test Admin',email=ADMIN,password=PASSWORD)
        post(admin,'setup',400,name='Test Admin',email=ADMIN,password=PASSWORD)
    else:
        sql(f"INSERT INTO users(name,email,password_hash,role) VALUES('Test Admin','{ADMIN}','{hashed}','admin')")
    post(admin,'login',email=ADMIN,password=PASSWORD)
    body=post(admin,'equipment',name='Test laptop',category='Laptop',asset_number=TAG)
    assert 'Equipment added.' in body
    equipment=sql(f"SELECT id FROM equipment WHERE asset_number='{TAG}'")
    post(staff,'register',name='Test Staff',email=STAFF,password=PASSWORD)
    post(staff,'login',email=STAFF,password=PASSWORD)
    args=dict(equipment_id=equipment,start_date=(date.today()+timedelta(days=3)).isoformat(),end_date=(date.today()+timedelta(days=4)).isoformat(),purpose='Integration test')
    post(staff,'book',**args)
    post(staff,'book',**args)
    ids=sql(f'SELECT id FROM bookings WHERE equipment_id={equipment} ORDER BY id').splitlines()
    assert len(ids)==2
    post(staff,'status',400,booking_id=ids[0],change='approve')
    post(admin,'status',booking_id=ids[0],change='approve')
    post(admin,'status',400,booking_id=ids[1],change='approve')
    post(staff,'book',400,**args)
    post(admin,'status',booking_id=ids[0],change='collect')
    post(admin,'status',booking_id=ids[0],change='return')
    post(staff,'status',booking_id=ids[1],change='cancel')
    assert sql(f'SELECT status FROM bookings WHERE id={ids[0]}')=='Returned'
    assert sql(f'SELECT status FROM bookings WHERE id={ids[1]}')=='Cancelled'
    try:
        staff.open(BASE,urllib.parse.urlencode(dict(action='equipment',csrf='invalid')).encode())
        raise AssertionError('CSRF token was accepted')
    except urllib.error.HTTPError as error:
        assert error.code==400
    post(staff,'logout')
    assert 'Welcome back' in get(staff)
    print('PASS: PHP/MySQL registration, login, equipment creation, booking, admin permissions, overlap protection, collection, return, cancellation, CSRF and logout.')
finally:
    sql(f"DELETE b FROM bookings b JOIN equipment e ON e.id=b.equipment_id WHERE e.asset_number='{TAG}'")
    sql(f"DELETE FROM equipment WHERE asset_number='{TAG}'")
    sql(f"DELETE FROM users WHERE email IN ('{ADMIN}','{STAFF}')")
