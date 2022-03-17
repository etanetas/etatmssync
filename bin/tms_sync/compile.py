from distutils.core import setup
from distutils.extension import Extension
from Cython.Distutils import build_ext

ext_modules = [
    Extension("api",  ["lib/api.py"]),
    Extension("models",  ["lib/models.py"]),
    Extension('creep',  ['lib/creep.py'])

#   ... all your modules that need be compiled ...

]

setup(
    name = 'TMS Sync',
    cmdclass = {'build_ext': build_ext},
    ext_modules = ext_modules
)